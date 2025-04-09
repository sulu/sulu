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
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Resolver\SettingsResolver;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;
use Sulu\Content\Application\ResourceLoader\ResourceLoaderProvider;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Webmozart\Assert\Assert;

/**
 * @phpstan-import-type SettingsData from SettingsResolver
 */
class ContentResolver implements ContentResolverInterface
{
    // TODO add configurable parameter for max depth
    private const MAX_DEPTH = 5;

    /**
     * @param iterable<ResolverInterface> $contentResolvers
     */
    public function __construct(
        private iterable $contentResolvers,
        private ResourceLoaderProvider $resourceLoaderProvider,
        private ContentAggregatorInterface $contentAggregator,
    ) {
    }

    public function resolve(DimensionContentInterface $dimensionContent): array
    {
        $locale = $dimensionContent->getLocale();
        Assert::string($locale, 'Locale must be a string');
        $stage = $dimensionContent->getStage();

        // Initial resolution to gather ResolvableResources
        /** @var array<int, array<string, array<int, array<int|string, ResolvableResource>>>> $priorityQueue */
        $priorityQueue = [];
        $resolvedResources = [];

        $resolvedContent = $this->resolveInternal($dimensionContent, 0, $priorityQueue);
        // Process the priority queue until it's empty
        while (!empty($priorityQueue)) {
            // Get the highest priority resources (first key due to krsort in mergeResolvableResources)
            $highestPriorityKey = \array_key_first($priorityQueue);
            $resourcesAtPriority = $priorityQueue[$highestPriorityKey];
            unset($priorityQueue[$highestPriorityKey]);

            $loaderIdDepths = [];
            $resourcesToLoad = [];
            // filter resources with depth too high
            foreach ($resourcesAtPriority as $loaderKey => $resourcePerDepth) {
                foreach ($resourcePerDepth as $depth => $resources) {
                    if ($depth > self::MAX_DEPTH) {
                        continue;
                    }
                    foreach ($resources as $id => $resource) {
                        $resourcesToLoad[$loaderKey][$id] = $resource;
                        $loaderIdDepths[$loaderKey][$id] = $depth;
                    }
                }
            }

            // Load resources at this priority level
            /** @var array<string, array<string, ResolvableResource>> $resourcesToLoad */
            $loadedResources = $this->loadResources($resourcesToLoad, $locale);

            // Process loaded resources
            foreach ($loadedResources as $loaderKey => $resources) {
                foreach ($resources as $id => $resource) {
                    if ($resource instanceof ContentRichEntityInterface) {
                        // Get the dimension content for this entity
                        $resourceDimension = $this->contentAggregator->aggregate($resource, [
                            'locale' => $locale,
                            'stage' => $stage,
                        ]);

                        // Resolve this entity
                        $depth = $loaderIdDepths[$loaderKey][$id];
                        $result = $this->resolveInternal($resourceDimension, $depth, $priorityQueue);
                        $resolvedValue = $this->normalizeContentData(
                            $result['content'],
                            $result['view'],
                            $resource
                        );
                    } else {
                        // For non-entity resources, just store the resource directly
                        $resolvedValue = $resource;
                    }

                    $resolvedResources[$loaderKey][$id] = $resolvedValue;
                }
            }
        }

        // Replace all ResolvableResource references with their actual resolved values
        $finalContent = $this->replaceResolvableResourcesWithResolvedValues(
            $resolvedContent['content'],
            $resolvedResources,
            1 // Start at depth 1 since the initial resolution was at depth 0
        );

        return $this->normalizeContentData(
            $finalContent,
            $resolvedContent['view'],
            $dimensionContent->getResource()
        );
    }

    /**
     * Internal method that resolves the DimensionContent and populates the priority queue.
     *
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     * @param int $depth Current depth
     * @param array<int, array<string, array<int, array<int|string, ResolvableResource>>>> $priorityQueue Reference to the priority queue
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, ResolvableResource>>>>,
     * }
     */
    private function resolveInternal(
        DimensionContentInterface $dimensionContent,
        int $depth,
        array &$priorityQueue
    ): array {
        $contentViews = $this->getContentViews($dimensionContent);
        $resolvedContent = $this->resolveContentViews($contentViews, $depth);

        // Add resolvable resources to priority queue
        $priorityQueue = $this->mergeResolvableResources(
            $resolvedContent['resolvableResources'],
            $priorityQueue
        );

        return $resolvedContent;
    }

    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     *
     * @return array<string|int, ContentView>
     */
    private function getContentViews(DimensionContentInterface $dimensionContent): array
    {
        $contentViews = [];

        /**
         * @var string $resolverKey
         * @var ResolverInterface $contentResolver
         */
        foreach ($this->contentResolvers as $resolverKey => $contentResolver) {
            $contentView = $contentResolver->resolve($dimensionContent);

            if (!$contentView instanceof ContentView) {
                continue;
            }

            $contentViews[$resolverKey] = $contentView;
        }

        return $contentViews;
    }

    /**
     * @param ContentView[] $contentViews
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, ResolvableResource>>>>,
     * }
     */
    private function resolveContentViews(array $contentViews, int $depth): array
    {
        $content = [];
        $view = [];

        $resolvableResources = [];
        foreach ($contentViews as $name => $contentView) {
            $result = $this->resolveContentView($contentView, (string) $name, $depth);
            $content = \array_merge($content, $result['content']);
            $view = \array_merge($view, $result['view']);
            $resolvableResources = $this->mergeResolvableResources($resolvableResources, $result['resolvableResources']);
        }

        return [
            'content' => $content,
            'view' => $view,
            'resolvableResources' => $resolvableResources,
            'depth' => $depth,
        ];
    }

    /**
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, ResolvableResource>>>>
     * }
     */
    private function resolveContentView(ContentView $contentView, string $name, int $depth): array
    {
        $content = $contentView->getContent();
        $view = $contentView->getView();

        $result = [
            'content' => [],
            'view' => [],
            'resolvableResources' => [],
            'depth' => $depth,
        ];
        if (\is_array($content)) {
            if (\count(\array_filter($content, fn ($entry) => $entry instanceof ContentView)) === \count($content)) {
                /** @var ContentView[] $content */
                // resolve array of content views
                $resolvedContentViews = $this->resolveContentViews($content, $depth + 1);
                $result['content'][$name] = $resolvedContentViews['content'];
                $result['view'][$name] = $resolvedContentViews['view'];
                $result['resolvableResources'] = $this->mergeResolvableResources($result['resolvableResources'], $resolvedContentViews['resolvableResources']);

                return $result;
            }

            $resolvableResources = [];
            foreach ($content as $key => $entry) {
                // resolve array of mixed content
                if ($entry instanceof ContentView) {
                    $resolvedContentView = $this->resolveContentView($entry, $key, $depth + 1);
                    $result['content'][$name] = \array_merge($result['content'][$name] ?? [], $resolvedContentView['content']);
                    $result['view'][$name] = \array_merge($result['view'][$name] ?? [], $resolvedContentView['view']);
                    $resolvableResources = $this->mergeResolvableResources($resolvableResources, $resolvedContentView['resolvableResources']);

                    continue;
                }

                if ($entry instanceof ResolvableResource) {
                    $resolvableResources[$entry->getPriority()][$entry->getResourceLoaderKey()][$depth][$entry->getId()] = $entry;
                }

                $result['content'][$name][$key] = $entry;
                $result['view'][$name][$key] = $view;
            }

            $result['resolvableResources'] = $resolvableResources;

            return $result;
        }

        if ($content instanceof ResolvableResource) {
            // @phpstan-ignore-next-line
            $result['resolvableResources'][$content->getPriority()][$content->getResourceLoaderKey()][$depth][$content->getId()] = $content;
        }

        $result['content'][$name] = $content;
        $result['view'][$name] = $view;

        return $result;
    }

    /**
     * Loads and resolves resources from various resource loaders.
     *
     * @param array<string, array<string, ResolvableResource>> $resourcesPerLoader Resource loaders and their associated resources to load
     *
     * @return array<string, mixed[]> Resolved resources organized by resource loader key
     */
    private function loadResources(array $resourcesPerLoader, ?string $locale): array
    {
        $loadedResources = [];
        foreach ($resourcesPerLoader as $loaderKey => $resourcesToLoad) {
            if (!$loaderKey) {
                throw new \RuntimeException(\sprintf('ResourceLoader key "%s" is invalid', $loaderKey));
            }

            $resourceLoader = $this->resourceLoaderProvider->getResourceLoader($loaderKey);
            if (!$resourceLoader) {
                throw new \RuntimeException(\sprintf('ResourceLoader with key "%s" not found', $loaderKey));
            }

            $resourceIds = \array_map(fn (ResolvableResource $resource) => $resource->getId(), $resourcesToLoad);
            $loadedResources[$loaderKey] = $resourceLoader->load(
                $resourceIds,
                $locale
            );
        }

        return $loadedResources;
    }

    /**
     * @param array<string, mixed> $content
     * @param array<string, mixed[]> $resolvedResources
     *
     * @return array<string, mixed>
     */
    private function replaceResolvableResourcesWithResolvedValues(array $content, array $resolvedResources, int $depth): array
    {
        if ($depth > self::MAX_DEPTH) {
            // replace non resolved resources with null
            \array_walk_recursive($content, function(&$value) {
                if ($value instanceof ResolvableResource) {
                    // TODO add callback with exception in dev mode
                    $value = null;
                }
            });

            return $content;
        }

        if (0 === \count($resolvedResources)) {
            return $content;
        }

        $hasReplaced = false;
        \array_walk_recursive($content, function(&$value) use ($resolvedResources, &$hasReplaced) {
            if ($value instanceof ResolvableResource && isset($resolvedResources[$value->getResourceLoaderKey()][$value->getId()])) {
                $value = $value->executeResourceCallback(
                    $resolvedResources[$value->getResourceLoaderKey()][$value->getId()]
                );
                $hasReplaced = true;
            }
        });

        // Recursively replace ResolvableResource instances in nested arrays
        // if a replacement was made in the previous step.
        // This is necessary to resolve nested ResolvableResource instances
        // which might have been added during the first replacement,
        // e.g., when a ResolvableResource is replaced with an array containing another ResolvableResource.
        if ($hasReplaced) {
            $content = $this->replaceResolvableResourcesWithResolvedValues($content, $resolvedResources, $depth + 1);
        }

        return $content;
    }

    /**
     * Merges the given resolvable resources with the existing resolvable resources.
     * The resolvable resources are ordered by priority and indexed by priority, loader key and object id.
     *
     * @param array<int, array<string, array<int, array<string|int, ResolvableResource>>>> $resolvableResources
     * @param array<int, array<string, array<int, array<string|int, ResolvableResource>>>> $existingResolvableResources
     *
     * @return array<int, array<string, array<int, array<string|int, ResolvableResource>>>>
     */
    private function mergeResolvableResources(array $resolvableResources, array $existingResolvableResources): array
    {
        foreach ($resolvableResources as $priority => $loaderResolvableResources) {
            foreach ($loaderResolvableResources as $loaderKey => $resolvableResourcesPerLoader) {
                foreach ($resolvableResourcesPerLoader as $depth => $resolvableResourcePerDepth) {
                    foreach ($resolvableResourcePerDepth as $resolvableResource) {
                        $existingResolvableResources[$priority][$loaderKey][$depth][$resolvableResource->getId()] = $resolvableResource;
                    }
                }
            }
        }
        \krsort($existingResolvableResources);

        return $existingResolvableResources;
    }

    /**
     * @template T of DimensionContentInterface
     *
     * @param array<string, mixed> $content
     * @param array<string, mixed> $view
     * @param ContentRichEntityInterface<T> $resource
     *
     * @return array{
     *     resource: ContentRichEntityInterface<T>,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     * }
     */
    private function normalizeContentData(array $content, array $view, ContentRichEntityInterface $resource): array
    {
        /** @var array<string, mixed> $templateData */
        $templateData = $content['template'] ?? [];
        unset($content['template']);

        /** @var array<string, mixed> $templateView */
        $templateView = $view['template'] ?? [];
        unset($view['template']);

        /** @var SettingsData $settingsData */
        $settingsData = $content['settings'] ?? [];
        unset($content['settings'], $view['settings']);

        /** @var array<string, array<string, mixed>> $extensionData */
        $extensionData = $content;

        return \array_merge(
            [
                'resource' => $resource,
                'content' => $templateData,
                'view' => $templateView,
                'extension' => $extensionData,
            ],
            $settingsData
        );
    }
}
