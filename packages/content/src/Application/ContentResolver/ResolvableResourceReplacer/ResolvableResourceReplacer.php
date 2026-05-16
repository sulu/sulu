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

namespace Sulu\Content\Application\ContentResolver\ResolvableResourceReplacer;

use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\ResolvableInterface;
use Sulu\Content\Application\ContentResolver\Value\ResolvableResource;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ResolvableResourceReplacer implements ResolvableResourceReplacerInterface
{
    public function __construct(
        private ReferenceStoreInterface $referenceStore
    ) {
    }

    /**
     * @param array<int|string, mixed> $content
     * @param array<string, array<string|int, array<string, array{resolved: mixed, contentViewEnhancement: ContentView}>>> $resolvedResources
     *
     * @return array{
     *     content: array<int|string, mixed>,
     *     viewEnhancements: array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}>,
     * }
     */
    public function replaceResolvableResourcesWithResolvedValues(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array {
        /** @var array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements */
        $viewEnhancements = [];

        $transformedContent = $this->replaceRecursively(
            $content,
            $resolvedResources,
            $depth,
            $maxDepth,
            [],
            $viewEnhancements
        );

        return [
            'content' => $transformedContent,
            'viewEnhancements' => $viewEnhancements,
        ];
    }

    /**
     * @param array<int|string, mixed> $content
     * @param array<string, array<string|int, array<string, array{resolved: mixed, contentViewEnhancement: ContentView}>>> $resolvedResources
     * @param list<int|string> $path Current path in the content structure
     * @param array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements
     *
     * @return array<int|string, mixed>
     */
    private function replaceRecursively(
        array $content,
        array $resolvedResources,
        int $depth,
        int $maxDepth,
        array $path,
        array &$viewEnhancements
    ): array {
        if ($depth > $maxDepth) {
            return $this->replaceUnresolvedWithNull($content);
        }

        if (0 === \count($resolvedResources)) {
            return $content;
        }

        $onlyResolvableResources = true;
        foreach ($content as $key => $value) {
            $currentPath = [...$path, $key];

            if ($value instanceof ResolvableInterface) {
                $resolveResult = $this->resolveValue($value, $resolvedResources);
                $resolved = $resolveResult['resolved'];
                $contentViewEnhancement = $resolveResult['contentViewEnhancement'];

                $contentData = $contentViewEnhancement->getContent();
                if (\is_array($contentData) && [] !== $contentData && \is_array($resolved)) {
                    $resolved = \array_merge($resolved, $contentData);
                }

                if ($value instanceof ResolvableResource) {
                    $viewData = $contentViewEnhancement->getView();
                    if ([] !== $viewData) {
                        // Numeric keys share the parent bucket so collection item views aggregate.
                        $viewPath = \is_int($key) ? $path : $currentPath;
                        if ([] !== $viewPath) {
                            $pathKey = $this->buildPropertyPath($viewPath);
                            $itemsPropertyName = $value->getItemsPropertyName();

                            if (!isset($viewEnhancements[$pathKey])) {
                                $viewEnhancements[$pathKey] = [
                                    'path' => $viewPath,
                                    'itemsPropertyName' => $itemsPropertyName,
                                    'items' => [],
                                ];
                            }
                            $viewEnhancements[$pathKey]['items'][] = $viewData;
                        }
                    }
                }

                $content[$key] = $resolved;

                if (\is_array($content[$key])) {
                    $content[$key] = $this->replaceRecursively(
                        $content[$key],
                        $resolvedResources,
                        $depth + 1,
                        $maxDepth,
                        $currentPath,
                        $viewEnhancements
                    );
                }
                continue;
            }
            $onlyResolvableResources = false;

            if (\is_array($value)) {
                $content[$key] = $this->replaceRecursively(
                    $value,
                    $resolvedResources,
                    $depth, // only increase depth for ResolvableInterface
                    $maxDepth,
                    $currentPath,
                    $viewEnhancements
                );
            }
        }

        // this is required for e.g. selections where some entities are not resolved
        // due to insufficient permissions
        if ($onlyResolvableResources) {
            // remove non-resolved resources if all values were resolvable resources
            $isList = \array_is_list($content);
            $content = \array_filter($content, static fn ($value) => null !== $value);
            if ($isList) {
                $content = \array_values($content);
            }
        }

        return $content;
    }

    /**
     * @param list<int|string> $path
     */
    private function buildPropertyPath(array $path): string
    {
        return '[' . \implode('][', $path) . ']';
    }

    /**
     * @param array<string, array<string|int, array<string, array{resolved: mixed, contentViewEnhancement: ContentView}>>> $resolvedResources
     *
     * @return array{contentViewEnhancement: ContentView, resolved: mixed}
     */
    private function resolveValue(
        ResolvableInterface $value,
        array $resolvedResources
    ): array {
        $loaderKey = $value->getResourceLoaderKey();
        $id = $value->getId();
        $metadataIdentifier = $value->getMetadataIdentifier();

        $entry = $resolvedResources[$loaderKey][$id][$metadataIdentifier] ?? null;

        if (null === $entry) {
            return ['contentViewEnhancement' => ContentView::create([], []), 'resolved' => null];
        }

        if ($value instanceof ResolvableResource && $value->getResourceKey()) {
            $this->populateReferenceStore($value->getId(), $value->getResourceKey());
        }

        return [
            'contentViewEnhancement' => $entry['contentViewEnhancement'],
            'resolved' => $value->executeResourceCallback($entry['resolved']),
        ];
    }

    /**
     * @param array<int|string, mixed> $content
     *
     * @return array<int|string, mixed>
     */
    private function replaceUnresolvedWithNull(array $content): array
    {
        foreach ($content as $key => $value) {
            if ($value instanceof ResolvableResource) {
                $content[$key] = null;
            } elseif (\is_array($value)) {
                $content[$key] = $this->replaceUnresolvedWithNull($value);
            }
        }

        return $content;
    }

    private function populateReferenceStore(string|int $resourceId, string $resourceKey): void
    {
        $this->referenceStore->add((string) $resourceId, $resourceKey);
    }

    public function replaceResolvableResourcesInView(
        array $view,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array {
        if (0 === \count($resolvedResources)) {
            return $view;
        }

        /** @var array<string, mixed> $result */
        $result = $this->replaceInViewRecursively($view, $resolvedResources, $depth, $maxDepth);

        return $result;
    }

    /**
     * @param array<int|string, mixed> $view
     * @param array<string, array<string|int, array<string, array{resolved: mixed, contentViewEnhancement: ContentView}>>> $resolvedResources
     *
     * @return array<int|string, mixed>
     */
    private function replaceInViewRecursively(
        array $view,
        array $resolvedResources,
        int $depth,
        int $maxDepth
    ): array {
        if ($depth > $maxDepth) {
            return $this->replaceUnresolvedWithNull($view);
        }

        foreach ($view as $key => $value) {
            if ($value instanceof ResolvableInterface) {
                $resolveResult = $this->resolveValue($value, $resolvedResources);
                $view[$key] = $resolveResult['resolved'];
                continue;
            }

            if (\is_array($value)) {
                // only increase depth for ResolvableInterface, matching replaceRecursively
                $view[$key] = $this->replaceInViewRecursively($value, $resolvedResources, $depth, $maxDepth);
            }
        }

        return $view;
    }
}
