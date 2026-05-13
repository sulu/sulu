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
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Application\ContentResolver\DataNormalizer\ContentViewDataNormalizerInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceLoader\ResolvableResourceLoaderInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessorInterface;
use Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer\ResolvableResourceReplacerInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderContentViewEnhancementInterface;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Webmozart\Assert\Assert;

/**
 * Resolves a DimensionContent into the API response data shape.
 *
 * Data flow:
 *
 *  +------------------+
 *  | DimensionContent |
 *  +------------------+
 *           |
 *           v
 *  +--------------------------+
 *  | ContentEnhancer::enhance |
 *  +--------------------------+
 *           |
 *           | enriches DimensionContent before resolver extraction
 *           v
 *  +---------------------+
 *  | ContentViewResolver |
 *  +---------------------+
 *           |
 *           | calls content/property resolvers
 *           | returns content + view + ResolvableResource queue
 *           v
 *  +----------------+
 *  | priority queue |
 *  +----------------+
 *           |
 *           | repeat: extract highest priority resources until empty
 *           v
 *  +----------------------+
 *  | loadResources        |       uses ResourceLoader::load()
 *  +----------------------+       hook: ResourceLoaderContentViewEnhancementInterface
 *                                 adds metadata available only after loading
 *           |
 *           v
 *  +------------------+
 *  | loaded resource  |
 *  +------------------+
 *           |
 *           +-- ContentRichEntityInterface ----------------------------+
 *           |      |                                                   |
 *           |      v                                                   |
 *           |  aggregate child DimensionContent                        |
 *           |      |                                                   |
 *           |      v                                                   |
 *           |  resolve child content recursively                       |
 *           |      |                                                   |
 *           |      v                                                   |
 *           |  normalize child content/view with child resource        |
 *           |      |                                                   |
 *           |      v                                                   |
 *           |  apply requested property mapping                        |
 *           |      |                                                   |
 *           |      +-- content enhancement: merge immediately          |
 *           |      |   so loader-level metadata is always present      |
 *           |      |                                                   |
 *           |      +-- view enhancement: defer until parent path exists|
 *           |                                                          |
 *           +-- ContentView ------------------------------------------+
 *           |      resolve nested ContentView content/view             |
 *           |      merge newly queued ResolvableResource values        |
 *           |      store resolved value + ContentView enhancement      |
 *           |                                                          |
 *           +-- raw resource -----------------------------------------+
 *           |      store resource value + ContentView enhancement
 *           |
 *           v
 *  +----------------------------+
 *  | ResolvableResourceReplacer |
 *  +----------------------------+
 *           |
 *           | replace content placeholders
 *           | merge deferred content enhancements when possible
 *           | collect view enhancements by original parent path
 *           v
 *  +--------------------------------------+
 *  | apply viewEnhancements to root view  |
 *  | via mergeViewEnhancement()           |
 *  +--------------------------------------+
 *           |
 *           | resolvedContent['view']
 *           v
 *  +---------------------------+
 *  | ContentViewDataNormalizer |
 *  +---------------------------+
 *           |
 *           | normalize final content + view
 *           | replace nested ContentView instances in content
 *           | merge field-level item view data into items
 *           | apply optional root property mapping
 *           |
 *           v
 *  array{resource: object, content: array, view: array, extension: array}
 *
 * @final
 */
readonly class ContentResolver implements ContentResolverInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private ContentViewResolverInterface $contentViewResolver,
        private ResolvableResourceLoaderInterface $resolvableResourceLoader,
        private ResolvableResourceQueueProcessorInterface $resolvableResourceQueueProcessor,
        private ResolvableResourceReplacerInterface $resolvableResourceReplacer,
        private ContentViewDataNormalizerInterface $contentViewDataNormalizer,
        private ContentAggregatorInterface $contentAggregator,
        private int $maxDepth,
        private ContentEnhancerInterface $contentEnhancer,
        private ResourceLoaderProvider $resourceLoaderProvider
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): array
    {
        $locale = $dimensionContent->getLocale();
        Assert::string($locale, 'Locale must be a string');

        // Pass shadow base locale as context for resource loader fallback.
        $context = [];
        if ($dimensionContent instanceof ShadowInterface && null !== $dimensionContent->getShadowLocale()) {
            $context['_shadowLocale'] = $dimensionContent->getShadowLocale();
        }

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
            $loadedResources = $this->resolvableResourceLoader->loadResources($resourcesToLoad, $locale, $context);

            // Process loaded resources
            foreach ($loadedResources as $loaderKey => $resources) {
                $resourceLoader = $this->resourceLoaderProvider->getResourceLoader($loaderKey);
                $supportsContentViewEnhancement = $resourceLoader instanceof ResourceLoaderContentViewEnhancementInterface;

                foreach ($resources as $id => $resourcePerMetadataIdentifier) {
                    $depth = $loaderIdDepths[$loaderKey][$id];
                    foreach ($resourcePerMetadataIdentifier as $metadataIdentifier => $resource) {
                        if ($resource instanceof ContentRichEntityInterface) {
                            // For content-rich entities, get the dimension content and resolve it
                            $childContent = $this->contentAggregator->aggregate($resource, [
                                'locale' => $locale,
                                'stage' => DimensionContentInterface::STAGE_LIVE,
                            ]);

                            // Retry with shadow base locale if child has no content in page locale.
                            $shadowLocale = $context['_shadowLocale'] ?? null;
                            if ($childContent instanceof TemplateInterface
                                && (null === $childContent->getTemplateKey() || '' === $childContent->getTemplateKey())
                                && \is_string($shadowLocale)
                            ) {
                                $childContent = $this->contentAggregator->aggregate($resource, [
                                    'locale' => $shadowLocale,
                                    'stage' => DimensionContentInterface::STAGE_LIVE,
                                ]);
                            }

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

                            // Content enhancements must be merged into normalized content after property mapping,
                            // otherwise loader-level metadata could be filtered out or merged at the wrapper level later.
                            // View enhancements depend on the parent property path and are applied after replacement.
                            $deferredViewData = [];
                            if ($supportsContentViewEnhancement) {
                                $contentViewEnhancement = $resourceLoader->resolveContentViewEnhancement($childContent);
                                $contentEnhancement = $contentViewEnhancement->getContent();

                                if (\is_array($contentEnhancement) && [] !== $contentEnhancement) {
                                    /** @var array<string, mixed> $existingContent */
                                    $existingContent = $resolvedValue['content'];
                                    $resolvedValue['content'] = \array_merge($existingContent, $contentEnhancement);
                                }

                                $deferredViewData = $contentViewEnhancement->getView();
                            }

                            $resolvedResources[$loaderKey][$id][$metadataIdentifier] = [
                                'contentViewEnhancement' => ContentView::create([], $deferredViewData),
                                'resolved' => $resolvedValue,
                            ];
                            continue;
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
                                'view' => $normalizedContentData['view']['0'],
                            ];

                            // Add resolvable resources to priority queue
                            $priorityQueue = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
                                $normalizedContentData['resolvableResources'],
                                $priorityQueue,
                            );

                            $sourceValue = $resolvedValue;
                        } else {
                            $resolvedValue = $resource;
                            $sourceValue = $resource;
                        }

                        $contentViewEnhancement = $supportsContentViewEnhancement
                            ? $resourceLoader->resolveContentViewEnhancement($sourceValue)
                            : ContentView::create([], []);

                        $resolvedResources[$loaderKey][$id][$metadataIdentifier] = [
                            'contentViewEnhancement' => $contentViewEnhancement,
                            'resolved' => $resolvedValue,
                        ];
                    }
                }
            }
        }

        $replacerResult = $this->resolvableResourceReplacer->replaceResolvableResourcesWithResolvedValues(
            $resolvedContent['content'],
            $resolvedResources,
            1, // Start at depth 1 since the initial resolution was at depth 0
            $this->maxDepth,
        );

        /** @var array<string, mixed> $finalContent */
        $finalContent = $replacerResult['content'];
        /** @var array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements */
        $viewEnhancements = $replacerResult['viewEnhancements'];

        foreach ($viewEnhancements as $path => $enhancement) {
            $items = $enhancement['items'];
            if ([] === $items) {
                continue;
            }

            $existing = $this->propertyAccessor->getValue($resolvedContent['view'], $path);
            $existingView = \is_array($existing) ? $existing : [];
            $resolvedContentValue = $this->propertyAccessor->getValue($finalContent, $path);
            $isCollection = \is_array($resolvedContentValue) && \array_is_list($resolvedContentValue);

            $merged = $this->mergeViewEnhancement(
                $existingView,
                $items,
                $enhancement['itemsPropertyName'],
                $isCollection
            );
            $this->propertyAccessor->setValue($resolvedContent['view'], $path, $merged);
        }

        /** @var array<string, mixed> $viewData */
        $viewData = $resolvedContent['view'];

        // view resolvables run after viewEnhancements so enhancement merges can layer on top of resolved view values
        /** @var array<string, mixed> $viewData */
        $viewData = $this->resolvableResourceReplacer->replaceResolvableResourcesInView(
            $viewData,
            $resolvedResources,
        );

        $normalizedContentData = $this->contentViewDataNormalizer->normalizeContentViewData(
            $finalContent,
            $viewData,
            $dimensionContent->getResource(),
        );

        $this->contentViewDataNormalizer->replaceNestedContentViews(
            $normalizedContentData,
            ['content']
        );

        $normalizedContentData = $this->mergeFieldViewDataIntoItems($normalizedContentData, $viewEnhancements);

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
        $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);

        $contentViews = $this->contentViewResolver->getContentViews($dimensionContent, $properties);
        $resolvedContent = $this->contentViewResolver->resolveContentViews($contentViews, $depth, $priorityQueue);

        // Add resolvable resources to priority queue
        $priorityQueue = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
            $resolvedContent['resolvableResources'],
            $priorityQueue,
        );

        return $resolvedContent;
    }

    /**
     * @param array<string|int, mixed> $existingView
     * @param list<mixed> $items
     *
     * @return array<string|int, mixed>
     */
    private function mergeViewEnhancement(array $existingView, array $items, ?string $itemsPropertyName, bool $isCollection): array
    {
        if (null === $itemsPropertyName) {
            // Single selections are object-like and flat-merge their one resolved view.
            // Collections stay list-like even when they currently contain only one item.
            if (!$isCollection && 1 === \count($items) && \is_array($items[0])) {
                return \array_merge($existingView, $items[0]);
            }

            return \array_merge($existingView, $items);
        }

        return \array_merge($existingView, [$itemsPropertyName => $items]);
    }

    /**
     * Folds per-item field-level view data sitting at numeric indices into the
     * corresponding `items` entry produced by viewEnhancements.
     *
     * @param array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>} $normalizedContentData
     * @param array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements
     *
     * @return array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>}
     */
    private function mergeFieldViewDataIntoItems(array $normalizedContentData, array $viewEnhancements): array
    {
        foreach ($viewEnhancements as $enhancement) {
            $itemsPropertyName = $enhancement['itemsPropertyName'];
            if (null === $itemsPropertyName) {
                continue;
            }

            // strip the resolver-key segment that normalizeContentViewData flattens into [view]
            $pathSegments = $enhancement['path'];
            \array_shift($pathSegments);
            if ([] === $pathSegments) {
                continue;
            }

            $viewPath = '[view]' . $this->buildPropertyPath($pathSegments);
            if (!$this->propertyAccessor->isReadable($normalizedContentData, $viewPath)) {
                continue;
            }

            $viewValue = $this->propertyAccessor->getValue($normalizedContentData, $viewPath);
            if (!\is_array($viewValue) || !isset($viewValue[$itemsPropertyName])) {
                continue;
            }

            /** @var list<array<string, mixed>> $items */
            $items = $viewValue[$itemsPropertyName];
            foreach ($items as $index => $item) {
                if (isset($viewValue[$index]) && \is_array($viewValue[$index])) {
                    $items[$index] = \array_merge($item, $viewValue[$index]);
                    unset($viewValue[$index]);
                }
            }

            $viewValue[$itemsPropertyName] = $items;
            $this->propertyAccessor->setValue($normalizedContentData, $viewPath, $viewValue);
        }

        /** @var array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>} $normalizedContentData */
        return $normalizedContentData;
    }

    /**
     * @param list<int|string> $segments
     */
    private function buildPropertyPath(array $segments): string
    {
        return '[' . \implode('][', $segments) . ']';
    }
}
