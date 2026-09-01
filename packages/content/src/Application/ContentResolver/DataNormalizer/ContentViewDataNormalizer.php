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

use Psr\Log\LoggerInterface;
use Sulu\Content\Application\ContentResolver\Exception\ResolverPlacementException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ContentViewDataNormalizer implements ContentViewDataNormalizerInterface
{
    private const ENVELOPE_KEYS = ['resource', 'content', 'view', 'extension'];

    /**
     * @param array<string, list<string>> $paths resolver type to envelope path segments without the
     *                                           `[root]` anchor, set by ContentResolverPathPass
     */
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private array $paths,
        private ?LoggerInterface $logger = null,
        private bool $debug = false,
    ) {
    }

    /**
     * `$content` arrives in resolver priority order. `+` merging keeps existing keys, so the
     * higher priority resolver wins on collision.
     * Placement failures throw in debug and are logged and skipped otherwise, so broken resolver
     * output cannot break a rendered page.
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

            try {
                $this->mergeAt($result, $segments, $data, $type);

                if ([] !== $segments && 'content' === $segments[\count($segments) - 1]) {
                    /** @var array<string, mixed> $typeView */
                    $typeView = $view[$type] ?? [];
                    $this->mergeAt($result, [...\array_slice($segments, 0, -1), 'view'], $typeView, $type);
                }
            } catch (ResolverPlacementException $e) {
                if ($this->debug) {
                    throw $e;
                }

                $this->logger?->error('Skipped content resolver "{type}": {message}', [
                    'type' => $type,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
            }
        }

        /** @var array{resource: ContentRichEntityInterface<T>, content: array<string, mixed>, view: array<string, mixed>, extension: array<string, array<string, mixed>>, ...} $result */
        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @param list<string> $segments
     */
    private function mergeAt(array &$result, array $segments, mixed $data, string $type): void
    {
        if ([] === $segments) {
            if (!\is_array($data)) {
                throw new ResolverPlacementException($type, [], \sprintf('it returned %s instead of an array', \get_debug_type($data)));
            }

            /** @var array<string, mixed> $data */
            $reserved = \array_intersect(\array_keys($data), self::ENVELOPE_KEYS);
            if ([] !== $reserved) {
                throw new ResolverPlacementException($type, [], \sprintf('it returned the reserved envelope key(s) "%s"', \implode('", "', $reserved)));
            }

            $result += $data;

            return;
        }

        $last = \array_pop($segments);
        $target = &$result;
        $walked = [];
        foreach ($segments as $segment) {
            $walked[] = $segment;
            /** @var array<string, mixed> $target */
            if (\array_key_exists($segment, $target) && !\is_array($target[$segment])) {
                throw new ResolverPlacementException($type, $walked, \sprintf('that slot already holds %s', \get_debug_type($target[$segment])));
            }

            $target[$segment] ??= [];
            $target = &$target[$segment];
        }

        /** @var array<string, mixed> $target */
        if (!\array_key_exists($last, $target)) {
            $target[$last] = $data;

            return;
        }

        if (!\is_array($target[$last]) || !\is_array($data)) {
            throw new ResolverPlacementException($type, [...$walked, $last], \sprintf(
                'that slot already holds %s and cannot take %s',
                \get_debug_type($target[$last]),
                \get_debug_type($data),
            ));
        }

        $target[$last] += $data;
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
    private function replaceNestedContentViews(array &$contentData, array $path = ['content'], int $envelopeDepth = 0): void
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
                    $this->replaceNestedContentViews($contentData, [...$path, $key], $envelopeDepth);
                }

                if (!$this->isExtractableIterable($iterable)) {
                    continue;
                }

                if ('view' === $key) {
                    // truncate at the next nested 'content' segment so we keep only the outermost resolver wrapper
                    $nextContentIdx = $this->findSegmentAfter($path, 'content', $envelopeDepth + 1);
                    $viewPath = null === $nextContentIdx ? $path : \array_slice($path, 0, $nextContentIdx);

                    $value = null;
                    $viewLookupPathStr = $this->buildPropertyPath([...$viewPath, $key]);
                    if ($this->propertyAccessor->isReadable($contentData, $viewLookupPathStr)) {
                        $value = $this->propertyAccessor->getValue($contentData, $viewLookupPathStr);
                    }

                    if (null === $value || [] === $value) {
                        $value = $this->propertyAccessor->getValue($contentData, $this->buildPropertyPath([...$path, $key]));
                    }

                    // swap the segment that roots this envelope for 'view'
                    $viewPath = [
                        ...\array_slice($viewPath, 0, $envelopeDepth),
                        'view',
                        ...\array_slice($viewPath, $envelopeDepth + 1),
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
     * Runs replaceNestedContentViews for the root envelope and every configured `[root][x][content]`
     * envelope, so those envelopes flatten nested content the same way the root envelope does.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>,
     *     ...
     * } $contentData
     */
    public function replaceNestedContentViewsAtEnvelopes(array &$contentData): void
    {
        /** @var array<string, list<string>> $envelopes */
        $envelopes = ['content' => ['content']];

        foreach ($this->paths as $segments) {
            if ([] !== $segments && 'content' === $segments[\count($segments) - 1]) {
                $envelopes[\implode('/', $segments)] = $segments;
            }
        }

        foreach ($envelopes as $segments) {
            if (!$this->propertyAccessor->isReadable($contentData, $this->buildPropertyPath($segments))) {
                continue;
            }

            $this->replaceNestedContentViews($contentData, $segments, \count($segments) - 1);
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
     * `[template, teasers]` or `[example_root, related]`. The type resolves to its envelope
     * segments; if those end in `content`, the field's view lives at the sibling `view` key
     * of that same envelope. Otherwise the resolver has no view envelope and the enhancement
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
