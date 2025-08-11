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
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor
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

        /** @var array<string, array<string, mixed>> $extensionData */
        $extensionData = $content;

        $result = \array_merge(
            [
                'resource' => $resource,
                'content' => $templateData,
                'view' => $templateView,
                'extension' => $extensionData,
            ],
            $settingsData,
        );

        return $result;
    }

    /**
     * Replaces nested ContentViews in the formatted content data.
     *
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>
     * } $contentData
     */
    public function replaceNestedContentViews(array &$contentData, string $path = '[content]'): void
    {
        $pathValues = [];
        $iterable = $this->propertyAccessor->getValue($contentData, $path) ?? [];
        if (!\is_array($iterable)) {
            return;
        }

        /** @var string $key */
        foreach ($iterable as $key => $entry) {
            if (\is_array($entry)) {
                if ([] !== $entry) {
                    $this->replaceNestedContentViews($contentData, $path . '[' . $key . ']');
                }
                if ('view' === $key) {
                    $value = $this->propertyAccessor->getValue($contentData, $path . '[' . $key . ']');
                    // Replace 'content' with 'view' in the path
                    $viewPath = \substr_replace($path, '[view]', 0, 9);

                    // If there are more [content] positions, we need to remove them, only keep the first one from the root property resolver
                    $viewPath = (($nextContentPosition = \strpos($viewPath, '[content]')) !== false) ? \substr($viewPath, 0, $nextContentPosition) : $viewPath;

                    // Only override empty view paths
                    if (($this->propertyAccessor->getValue($contentData, $viewPath) ?? []) === []) {
                        $pathValues[$viewPath] = $value;
                    }
                }
                if ('content' === $key) {
                    $value = $this->propertyAccessor->getValue($contentData, $path . '[' . $key . ']');
                    $pathValues[$path] = $value;
                }
            }
        }

        foreach ($pathValues as $path => $value) {
            $this->propertyAccessor->setValue($contentData, $path, $value); // @phpstan-ignore-line
        }
    }

    /**
     * @param array{
     *     resource: object,
     *     content: array<string, mixed>,
     *     view: array<string, mixed>,
     *     extension: array<string, array<string, mixed>>
     * } &$data
     * @param array<string, mixed> $properties
     */
    public function recursivelyMapProperties(
        array &$data,
        array $properties,
        string $path = '',
        int $depth = 0,
        bool $isRoot = true
    ): void {
        $iterable = '' === $path ? $data : ($this->propertyAccessor->getValue($data, $path) ?? []);
        if (!\is_array($iterable)) {
            return;
        }

        foreach ($iterable as $key => $value) {
            if (
                ($properties[$key] ?? null)
                && $depth === (\substr_count($path, '][') + 1)
            ) {
                $parent = $this->propertyAccessor->getValue($data, $path);
                if (!\is_array($parent)) {
                    continue;
                }
                unset($parent[$key]);
                // @phpstan-ignore-next-line
                $this->propertyAccessor->setValue($data, $path, $parent);

                if (!\is_string($key)) {
                    continue;
                }
                $rootPath = ($isRoot ? '' : '[content]') . '[' . \implode('][', \explode('.', $key)) . ']';
                // @phpstan-ignore-next-line
                $this->propertyAccessor->setValue($data, $rootPath, $value);
            }

            // do not walk into 'view' as views cannot be mapped via properties
            if (\is_array($value) && 'view' !== $key) {
                if (!\is_string($key)) {
                    continue;
                }
                $this->recursivelyMapProperties(
                    $data, // @phpstan-ignore-line
                    $properties,
                    $path . '[' . $key . ']',
                    $depth + 1,
                    $isRoot
                );
            }
        }
    }
}
