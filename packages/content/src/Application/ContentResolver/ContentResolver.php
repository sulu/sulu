<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Application\ContentResolver;

use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizerInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceLoader\ResolvableResourceLoaderInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessorInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacerInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

readonly class ContentResolver implements ContentResolverInterface
{
    public function __construct(
        private ContentViewResolverInterface $contentViewResolver,
        private ResolvableResourceLoaderInterface $resolvableResourceLoader,
        private ResolvableResourceQueueProcessorInterface $resolvableResourceQueueProcessor,
        private ResolvableResourceReplacerInterface $resolvableResourceReplacer,
        private ContentViewDataNormalizerInterface $contentViewDataNormalizer,
        private ContentAggregatorInterface $contentAggregator,
        private int $maxDepth
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): array
    {
        $locale = $dimensionContent->getLocale();
        Assert::string($locale, 'Locale must be a string');
        $stage = $dimensionContent->getStage();

        // Initial resolution to gather ResolvableResources
        /** @var array<int, array<string, array<int, array<int|string, array<string, ResolvableInterface>>>>> $priorityQueue */
        $priorityQueue = [];
        $resolvedResources = [];

        $resolvedContent = $this->resolveInternal($dimensionContent, 0, $priorityQueue, $properties);

        // Process the priority queue until it's empty
        while (!empty($priorityQueue)) {
            // Extract highest priority resources from the queue
            $extractedResources = $this->resolvableResourceQueueProcessor->extractHighestPriorityResources(
                $priorityQueue,
                $this->maxDepth
            );

            $resourcesToLoad = $extractedResources['resourcesToLoad'];
            $loaderIdDepths = $extractedResources['loaderIdDepths'];

            // Load resources at this priority level
            $loadedResources = $this->resolvableResourceLoader->loadResources($resourcesToLoad, $locale);

            // Process loaded resources
            foreach ($loadedResources as $loaderKey => $resources) {
                foreach ($resources as $id => $resourcePerMetadataIdentifier) {
                    $depth = $loaderIdDepths[$loaderKey][$id];
                    foreach ($resourcePerMetadataIdentifier as $metadataIdentifier => $resource) {
                        if ($resource instanceof ContentRichEntityInterface) {
                            // For content-rich entities, get the dimension content and resolve it
                            $childContent = $this->contentAggregator->aggregate($resource, [
                                'locale' => $locale,
                                'stage' => $stage,
                            ]);

                            /** @var ResolvableInterface $resolvableResource */
                            $resolvableResource = $resourcesToLoad[$loaderKey][$id][$metadataIdentifier];
                            $metadata = $resolvableResource->getMetadata();
                            /** @var array<string, string>|null $internalProperties */
                            $internalProperties = $metadata['properties'] ?? null;

                            $normalizedContentData = $this->resolveInternal($childContent, $depth + 1, $priorityQueue, $internalProperties);

                            $resolvedValue = $this->contentViewDataNormalizer->normalizeContentViewData(
                                $normalizedContentData['content'],
                                $normalizedContentData['view'],
                                $resource,
                            );

                            if (null !== $internalProperties && [] !== $internalProperties) {
                                $this->contentViewDataNormalizer->recursivelyMapProperties(
                                    data: $resolvedValue,
                                    properties: $internalProperties,
                                    isRoot: false
                                );
                            }
                        } elseif ($resource instanceof ContentView) {
                            /** @var array{
                             *     content: array{'0': array<string, mixed>},
                             *     view: array{'0': array<string, mixed>},
                             *     resolvableResources: array<int, array<string, array<int, array<int|string, array<string, ResolvableInterface>>>>>
                             * } $normalizedContentData
                             */
                            $normalizedContentData = $this->contentViewResolver->resolveContentView($resource, '0', $depth, $priorityQueue);
                            $resolvedValue = [
                                'content' => $normalizedContentData['content']['0'],
                                // All resolved resources have the same view structure, so we can just take the first one
                                'view' => \reset($normalizedContentData['view']['0']) ?? $normalizedContentData['view']['0'],
                            ];

                            // Add resolvable resources to priority queue
                            $priorityQueue = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
                                $normalizedContentData['resolvableResources'],
                                $priorityQueue,
                            );
                        } else {
                            // For non-entity resources, just store the resource directly
                            $resolvedValue = $resource;
                        }

                        $resolvedResources[$loaderKey][$id][$metadataIdentifier] = $resolvedValue;
                    }
                }
            }
        }

        // Replace all ResolvableResource references with their actual resolved values
        $finalContent = $this->resolvableResourceReplacer->replaceResolvableResourcesWithResolvedValues(
            $resolvedContent['content'],
            $resolvedResources,
            1, // Start at depth 1 since the initial resolution was at depth 0
            $this->maxDepth,
        );

        $normalizedContentData = $this->contentViewDataNormalizer->normalizeContentViewData(
            $finalContent,
            $resolvedContent['view'],
            $dimensionContent->getResource(),
        );

        $this->contentViewDataNormalizer->replaceNestedContentViews(
            $normalizedContentData,
            '[content]'
        );

        if (null !== $properties && [] !== $properties) {
            $this->contentViewDataNormalizer->recursivelyMapProperties(
                data: $normalizedContentData,
                properties: $properties,
            );
        }

        return $normalizedContentData;
    }

    /**
     * Internal method that resolves the DimensionContent and populates the priority queue.
     *
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param int $depth Current depth
     * @param array<int, array<string, array<int, array<int|string, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     * @param array<string, mixed>|null $properties
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>,
     * }
     */
    private function resolveInternal(
        DimensionContentInterface $dimensionContent,
        int $depth,
        array &$priorityQueue,
        ?array $properties = null
    ): array {
        $contentViews = $this->contentViewResolver->getContentViews($dimensionContent, $properties);
        $resolvedContent = $this->contentViewResolver->resolveContentViews($contentViews, $depth, $priorityQueue);

        // Add resolvable resources to priority queue
        $priorityQueue = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
            $resolvedContent['resolvableResources'],
            $priorityQueue,
        );

        return $resolvedContent;
    }
}
