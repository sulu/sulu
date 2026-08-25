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

use Sulu\Content\Application\ContentResolver\Resolver\SettingsResolver;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @phpstan-import-type SettingsData from SettingsResolver
 *
 * @internal This service is intended for internal use only within the package/library.
 * Modifying or depending on this service may result in unexpected behavior and is not supported.
 */
class ContentViewDataNormalizer implements ContentViewDataNormalizerInterface
{
    /**
     * @param list<string> $rootResolverKeys resolver keys placed at the root of the envelope instead of under `extension`
     */
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor,
        private array $rootResolverKeys = [],
    ) {
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
     *     ...
     * }
     */
    public function normalizeContentViewData(
        array $content,
        array $view,
        ContentRichEntityInterface $resource,
    ): array {
        /** @var array<string, mixed> $templateData */
        $templateData = $content['template'] ?? [];
        unset($content['template']);

        /** @var array<string, mixed> $templateView */
        $templateView = $view['template'] ?? [];
        unset($view['template']);

        /** @var SettingsData $settingsData */
        $settingsData = $content['settings'] ?? [];
        unset($content['settings'], $view['settings']);

        $rootData = [];
        foreach ($this->rootResolverKeys as $rootResolverKey) {
            if (!\array_key_exists($rootResolverKey, $content)) {
                continue;
            }

            $rootData[$rootResolverKey] = $content[$rootResolverKey];
            unset($content[$rootResolverKey], $view[$rootResolverKey]);
        }

        /** @var array<string, array<string, mixed>> $extensionData */
        $extensionData = $content;

        $withSettings = \array_merge(
            [
                'resource' => $resource,
                'content' => $templateData,
                'view' => $templateView,
                'extension' => $extensionData,
            ],
            $settingsData,
        );

        return $withSettings + $rootData;
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
    public function replaceNestedContentViews(array &$contentData, array $path = ['content']): void
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
                    $this->replaceNestedContentViews($contentData, [...$path, $key]);
                }

                if (!$this->isExtractableIterable($iterable)) {
                    continue;
                }

                if ('view' === $key) {
                    // truncate at the next nested 'content' segment so we keep only the outermost resolver wrapper
                    $nextContentIdx = $this->findSegmentAfter($path, 'content', 1);
                    $viewPath = null === $nextContentIdx ? $path : \array_slice($path, 0, $nextContentIdx);

                    $value = null;
                    $viewLookupPathStr = $this->buildPropertyPath([...$viewPath, $key]);
                    if ($this->propertyAccessor->isReadable($contentData, $viewLookupPathStr)) {
                        $value = $this->propertyAccessor->getValue($contentData, $viewLookupPathStr);
                    }

                    if (null === $value || [] === $value) {
                        $value = $this->propertyAccessor->getValue($contentData, $this->buildPropertyPath([...$path, $key]));
                    }

                    // swap the leading 'content' segment for 'view'
                    $viewPath[0] = 'view';
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
