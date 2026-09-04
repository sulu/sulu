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

namespace Sulu\Content\Application\ContentResolver\DataNormalizer;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ContentViewDataNormalizer implements ContentViewDataNormalizerInterface
{
    /**
     * @param array<string, list<string>> $paths resolver type to path segments without the
     *                                           `[root]` anchor, set by ContentResolverPathPass
     */
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private array $paths,
    ) {
    }

    /**
     * `$content` arrives in tagged iterator order, so the first resolver to write a key keeps it
     * and a higher tag `priority` beats a lower one. The envelope keys `content`, `view` and
     * `extension` are seeded below before any resolver runs, so a `[root]` resolver cannot write
     * them at any priority.
     *
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
     *     ...
     * }
     */
    public function normalizeContentViewData(
        array $content,
        array $view,
        ContentRichEntityInterface $resource,
    ): array {
        $result = [
            'resource' => $resource,
            'content' => [],
            'view' => [],
            'extension' => [],
        ];

        foreach ($content as $type => $data) {
            $segments = $this->paths[$type] ?? ['extension', $type];

            $result = $this->mergeResolverOutput($result, $segments, $data);

            if ([] !== $segments && 'content' === $segments[\count($segments) - 1]) {
                /** @var array<string, mixed> $typeView */
                $typeView = $view[$type] ?? [];
                $result = $this->mergeResolverOutput($result, [...\array_slice($segments, 0, -1), 'view'], $typeView);
            }
        }

        /** @var array{resource: ContentRichEntityInterface<T>, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...} $result */
        return $result;
    }

    /**
     * Merges one resolver's output into the content view data at its configured path.
     *
     * An empty path means the root itself. A free path takes the data as is, two arrays are
     * merged with `+`, and any other collision keeps what is already there. The merge knows
     * nothing about priority: it is first writer wins, and the caller iterates in priority order.
     *
     * @param array<string, mixed> $target
     * @param list<string> $segments
     *
     * @return array<string, mixed>
     */
    private function mergeResolverOutput(array $target, array $segments, mixed $data): array
    {
        if ([] === $segments) {
            if (!\is_array($data)) {
                throw new \LogicException(\sprintf(
                    'A content resolver on path "[root]" must return an array, got %s.',
                    \get_debug_type($data),
                ));
            }

            // the envelope keys are already set, so `+` keeps them
            /** @var array<string, mixed> $data */
            return $target + $data;
        }

        $segment = $segments[0];
        $rest = \array_slice($segments, 1);

        if (!\array_key_exists($segment, $target)) {
            $target[$segment] = [] === $rest ? $data : $this->mergeResolverOutput([], $rest, $data);

            return $target;
        }

        $existing = $target[$segment];
        if (!\is_array($existing)) {
            return $target;
        }

        if ([] === $rest) {
            if (\is_array($data)) {
                $target[$segment] = $existing + $data;
            }

            return $target;
        }

        /** @var array<string, mixed> $existing */
        $target[$segment] = $this->mergeResolverOutput($existing, $rest, $data);

        return $target;
    }

    /**
     * Replaces nested ContentViews in the formatted content data.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     *     ...
     * } $contentData
     * @param list<int|string> $path
     */
    private function replaceNestedContentViewsAtPath(array &$contentData, array $path = ['content'], int $contentDepth = 0): void
    {
        $pathValues = [];
        $iterable = $this->propertyAccessor->getValue($contentData, $this->buildPropertyPath($path)) ?? [];
        if (!\is_array($iterable)) {
            return;
        }

        /** @var string $key */
        foreach ($iterable as $key => $entry) {
            if (\is_array($entry)) {
                if ([] !== $entry) {
                    $this->replaceNestedContentViewsAtPath($contentData, [...$path, $key], $contentDepth);
                }

                if (!$this->isExtractableIterable($iterable)) {
                    continue;
                }

                if ('view' === $key) {
                    // truncate at the next nested 'content' segment so we keep only the outermost resolver wrapper
                    $nextContentIdx = $this->findSegmentAfter($path, 'content', $contentDepth + 1);
                    $viewPath = null === $nextContentIdx ? $path : \array_slice($path, 0, $nextContentIdx);

                    $value = null;
                    $viewLookupPathStr = $this->buildPropertyPath([...$viewPath, $key]);
                    if ($this->propertyAccessor->isReadable($contentData, $viewLookupPathStr)) {
                        $value = $this->propertyAccessor->getValue($contentData, $viewLookupPathStr);
                    }

                    if (null === $value || [] === $value) {
                        $value = $this->propertyAccessor->getValue($contentData, $this->buildPropertyPath([...$path, $key]));
                    }

                    // swap the segment that roots this content path for 'view'
                    $viewPath = [
                        ...\array_slice($viewPath, 0, $contentDepth),
                        'view',
                        ...\array_slice($viewPath, $contentDepth + 1),
                    ];
                    $viewPathStr = $this->buildPropertyPath($viewPath);

                    $existingViewData = $this->propertyAccessor->getValue($contentData, $viewPathStr) ?? [];
                    if (\is_array($existingViewData) && \is_array($value)) {
                        $pathValues[$viewPathStr] = [] === $existingViewData
                            ? $value
                            : \array_merge($value, $existingViewData);
                    }
                } elseif ('content' === $key) {
                    $value = $this->propertyAccessor->getValue($contentData, $this->buildPropertyPath([...$path, $key]));
                    $pathValues[$this->buildPropertyPath($path)] = $value;
                }
            }
        }

        foreach ($pathValues as $pathStr => $value) {
            $this->propertyAccessor->setValue($contentData, $pathStr, $value); // @phpstan-ignore-line
        }
    }

    /**
     * Runs the replacement for the root `content` and every configured `[root][x][content]` path,
     * so those paths flatten nested content the same way the root does.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     *     ...
     * } $contentData
     */
    public function replaceNestedContentViews(array &$contentData): void
    {
        /** @var array<string, list<string>> $contentPaths */
        $contentPaths = ['content' => ['content']];

        foreach ($this->paths as $segments) {
            if ([] !== $segments && 'content' === $segments[\count($segments) - 1]) {
                $contentPaths[\implode('/', $segments)] = $segments;
            }
        }

        foreach ($contentPaths as $segments) {
            if (!$this->propertyAccessor->isReadable($contentData, $this->buildPropertyPath($segments))) {
                continue;
            }

            $this->replaceNestedContentViewsAtPath($contentData, $segments, \count($segments) - 1);
        }
    }

    /**
     * @param array<int|string, mixed> $iterable
     */
    private function isExtractableIterable(array $iterable): bool
    {
        return
            \array_key_exists('content', $iterable)
            && \array_key_exists('view', $iterable)
            && (
                2 === \count($iterable) // SmartResolvedResource e.g. SmartContent do not have a resource
                || ($iterable['resource'] ?? null) instanceof ContentRichEntityInterface // resolved ContentRichEntities always have a resource
            );
    }

    /**
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     *     ...
     * } &$data
     * @param array<string, mixed> $properties
     * @param list<int|string> $path
     */
    public function recursivelyMapProperties(
        array &$data,
        array $properties,
        array $path = [],
        int $depth = 0,
        bool $isRoot = true
    ): void {
        $iterable = [] === $path ? $data : ($this->propertyAccessor->getValue($data, $this->buildPropertyPath($path)) ?? []);
        if (!\is_array($iterable)) {
            return;
        }

        foreach ($iterable as $key => $value) {
            if (
                ($properties[$key] ?? null)
                && $depth === \max(1, \count($path))
            ) {
                $pathStr = $this->buildPropertyPath($path);
                $parent = $this->propertyAccessor->getValue($data, $pathStr);
                if (!\is_array($parent)) {
                    continue;
                }
                unset($parent[$key]);
                // @phpstan-ignore-next-line
                $this->propertyAccessor->setValue($data, $pathStr, $parent);

                if (!\is_string($key)) {
                    continue;
                }
                $rootSegments = \explode('.', $key);
                $rootPath = $isRoot ? $rootSegments : ['content', ...$rootSegments];
                // @phpstan-ignore-next-line
                $this->propertyAccessor->setValue($data, $this->buildPropertyPath($rootPath), $value);
            }

            // do not walk into 'view' as views cannot be mapped via properties
            if (\is_array($value) && 'view' !== $key) {
                if (!\is_string($key)) {
                    continue;
                }
                $this->recursivelyMapProperties(
                    $data, // @phpstan-ignore-line
                    $properties,
                    [...$path, $key],
                    $depth + 1,
                    $isRoot
                );
            }
        }
    }

    /**
     * Folds per-item field-level view data sitting at numeric indices into the
     * corresponding `items` entry produced by viewEnhancements.
     *
     * An enhancement path starts with the resolver type that produced the field, e.g.
     * `[template, teasers]` or `[example_root, related]`. The type resolves to its path
     * segments; if those end in `content`, the field's view lives at the sibling `view` key
     * of that same path. Otherwise the resolver has no view data and the enhancement
     * is dropped.
     *
     * @param array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...} $data
     * @param array<string, array{path: list<int|string>, itemsPropertyName: ?string, items: list<mixed>}> $viewEnhancements
     *
     * @return array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...}
     */
    public function mergeFieldViewDataIntoItems(array $data, array $viewEnhancements): array
    {
        foreach ($viewEnhancements as $enhancement) {
            $itemsPropertyName = $enhancement['itemsPropertyName'];
            if (null === $itemsPropertyName) {
                continue;
            }

            $pathSegments = $enhancement['path'];
            $type = \array_shift($pathSegments);
            if ([] === $pathSegments || !\is_string($type)) {
                continue;
            }

            $segments = $this->paths[$type] ?? ['extension', $type];
            if ([] === $segments || 'content' !== $segments[\count($segments) - 1]) {
                continue;
            }

            $viewPath = $this->buildPropertyPath([...\array_slice($segments, 0, -1), 'view', ...$pathSegments]);
            if (!$this->propertyAccessor->isReadable($data, $viewPath)) {
                continue;
            }

            $viewValue = $this->propertyAccessor->getValue($data, $viewPath);
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
            $this->propertyAccessor->setValue($data, $viewPath, $viewValue);
        }

        /** @var array{resource: object, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...} $data */
        return $data;
    }

    /**
     * @param list<int|string> $segments
     */
    private function buildPropertyPath(array $segments): string
    {
        return '[' . \implode('][', $segments) . ']';
    }

    /**
     * @param list<int|string> $path
     */
    private function findSegmentAfter(array $path, string $segment, int $startIndex): ?int
    {
        $count = \count($path);
        for ($i = $startIndex; $i < $count; ++$i) {
            if ($path[$i] === $segment) {
                return $i;
            }
        }

        return null;
    }
}
