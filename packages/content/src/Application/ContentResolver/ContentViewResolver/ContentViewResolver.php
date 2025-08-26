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

namespace Sulu\Content\Application\ContentResolver\ContentViewResolver;

use Sulu\Content\Application\ContentResolver\ResolvableResourceQueue\ResolvableResourceQueueProcessorInterface;
use Sulu\Content\Application\ContentResolver\Resolver\ResolverInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ContentViewResolver implements ContentViewResolverInterface
{
    /**
     * @param iterable<string, ResolverInterface> $contentResolvers
     */
    public function __construct(
        private ResolvableResourceQueueProcessorInterface $resolvableResourceQueueProcessor,
        private iterable $contentResolvers
    ) {
    }

    /**
     * @param array<string, string>|null $properties
     */
    public function getContentViews(DimensionContentInterface $dimensionContent, ?array $properties = null): array
    {
        $contentViews = [];

        /**
         * @var string $resolverKey
         * @var ResolverInterface $contentResolver
         */
        foreach ($this->contentResolvers as $resolverKey => $contentResolver) {
            $contentView = $contentResolver->resolve($dimensionContent, $properties);

            if (!$contentView instanceof ContentView) {
                continue;
            }

            $contentViews[$resolverKey] = $contentView;
        }

        return $contentViews;
    }

    /**
     * @param ContentView[] $contentViews
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>,
     * }
     */
    public function resolveContentViews(array $contentViews, int $depth, array &$priorityQueue = []): array
    {
        $content = [];
        $view = [];

        $resolvableResources = [];
        foreach ($contentViews as $name => $contentView) {
            $result = $this->resolveContentView($contentView, (string) $name, $depth, $priorityQueue);
            $content = \array_merge($content, $result['content']);
            $view = \array_merge($view, $result['view']);
            $resolvableResources = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
                $resolvableResources,
                $result['resolvableResources']
            );
        }

        return [
            'content' => $content,
            'view' => $view,
            'resolvableResources' => $resolvableResources,
            'depth' => $depth,
        ];
    }

    /**
     * @param array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>> &$priorityQueue Reference to the priority queue
     *
     * @return array{
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     resolvableResources: array<int, array<string, array<int, array<string|int, array<string, ResolvableInterface>>>>>
     * }
     */
    public function resolveContentView(ContentView $contentView, string $name, int $depth, array &$priorityQueue = []): array
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
                $resolvedContentViews = $this->resolveContentViews($content, $depth + 1, $priorityQueue);
                $result['content'][$name] = $resolvedContentViews['content'];
                $result['view'][$name] = $resolvedContentViews['view'];
                $result['resolvableResources'] = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
                    $result['resolvableResources'],
                    $resolvedContentViews['resolvableResources']
                );

                return $result;
            }

            $resolvableResources = [];
            foreach ($content as $key => $entry) {
                // resolve array of mixed content
                if ($entry instanceof ContentView) {
                    $resolvedContentView = $this->resolveContentView($entry, $key, $depth + 1, $priorityQueue);
                    $result['content'][$name] = \array_merge($result['content'][$name] ?? [], $resolvedContentView['content']);
                    $result['view'][$name] = \array_merge($result['view'][$name] ?? [], $resolvedContentView['view']);
                    $resolvableResources = $this->resolvableResourceQueueProcessor->mergeResolvableResources(
                        $resolvableResources,
                        $resolvedContentView['resolvableResources']
                    );

                    continue;
                }

                if ($entry instanceof ResolvableInterface) {
                    $resolvableResources[$entry->getPriority()][$entry->getResourceLoaderKey()][$depth][$entry->getId()][$entry->getMetadataIdentifier()] = $entry;
                }

                $result['content'][$name][$key] = $entry;
                if (isset($view[$key])) {
                    $result['view'][$name][$key] = $view[$key];
                }
            }

            // If the view is not set for this name, we can use the root view
            if (($result['view'][$name] ?? null) === null) {
                $result['view'][$name] = $view;
            }

            $result['resolvableResources'] = $resolvableResources;

            return $result;
        }

        if ($content instanceof ResolvableInterface) {
            // @phpstan-ignore-next-line
            $result['resolvableResources'][$content->getPriority()][$content->getResourceLoaderKey()][$depth][$content->getId()][$content->getMetadataIdentifier()] = $content;
        }

        $result['content'][$name] = $content;
        $result['view'][$name] = $view;

        return $result;
    }
}
