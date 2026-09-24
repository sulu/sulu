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

namespace Sulu\Content\Application\MetadataResolver;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\OptionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\PropertyResolver\PropertyResolverProviderInterface;
use Sulu\Content\Application\PropertyResolver\Resolver\PropertyResolverMetadataAwareInterface;

/**
 * @internal This class is intended for internal use only within the library. Modifying or depending on this class may result in unexpected behavior and is not supported.
 */
class MetadataResolver
{
    public function __construct(
        private PropertyResolverProviderInterface $propertyResolverProvider
    ) {
    }

    /**
     * @param ItemMetadata[] $items
     * @param mixed[] $data
     *
     * @return array<string, ContentView>
     */
    public function resolveItems(array $items, array $data, string $locale): array
    {
        $contentViews = [];
        foreach ($items as $key => $item) {
            $name = (string) $key;
            $type = $item->getType();
            if ($item instanceof SectionMetadata) {
                $contentViews = \array_merge(
                    $contentViews,
                    $this->resolveItems($item->getItems(), $data, $locale)
                );
            } else {
                $params = $item instanceof FieldMetadata ? $this->serializeFieldMetadataOptions($item) : [];
                $contentViews[$name] = $this->resolveProperty(
                    $type,
                    $this->getValueByPath($data, $name),
                    $locale,
                    $item,
                    $params,
                );
            }
        }

        return $this->nestContentViews($contentViews, $data);
    }

    /**
     * @param mixed[] $data
     */
    private function getValueByPath(array $data, string $path): mixed
    {
        if (\array_key_exists($path, $data)) {
            return $data[$path];
        }

        if (!\str_contains($path, '/')) {
            return null;
        }

        $current = $data;
        foreach (\explode('/', $path) as $segment) {
            if (!\is_array($current) || !\array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Groups content views with slash-separated keys into nested structures
     * when the data uses nested arrays (not flat slash keys).
     *
     * @param array<string, ContentView> $contentViews
     * @param mixed[] $data
     *
     * @return array<string, ContentView>
     */
    private function nestContentViews(array $contentViews, array $data): array
    {
        $flatSlashRoots = [];
        foreach (\array_keys($data) as $key) {
            if (\str_contains($key, '/')) {
                [$root] = \explode('/', $key, 2);
                $flatSlashRoots[$root] = true;
            }
        }

        $result = [];
        foreach ($contentViews as $key => $view) {
            if (!\str_contains($key, '/') || \array_key_exists($key, $data)) {
                $result[$key] = $view;
                continue;
            }

            $segments = \explode('/', $key);
            /** @var string $root */
            $root = \array_shift($segments);

            if (isset($flatSlashRoots[$root])) {
                $result[$key] = $view;
                continue;
            }

            /** @var array<string, mixed> $rootContent */
            $rootContent = [];
            /** @var array<string, mixed> $rootView */
            $rootView = [];
            $existing = $result[$root] ?? null;
            if ($existing instanceof ContentView) {
                $existingContent = $existing->getContent();
                if (\is_array($existingContent)) {
                    $rootContent = $existingContent;
                }
                $rootView = $existing->getView();
            }

            $this->setNestedValue($rootContent, $segments, $view->getContent());
            if ([] !== $view->getView()) {
                $this->setNestedValue($rootView, $segments, $view->getView());
            }

            $result[$root] = ContentView::create($rootContent, $rootView);
        }

        return $result;
    }

    /**
     * @param array<mixed> &$data
     * @param string[] $segments
     */
    private function setNestedValue(array &$data, array $segments, mixed $value): void
    {
        if ([] === $segments) {
            return;
        }

        $current = &$data;
        foreach (\array_slice($segments, 0, -1) as $segment) {
            if (!\array_key_exists($segment, $current) || !\is_array($current[$segment])) {
                $current[$segment] = [];
            }

            /** @var array<string, mixed> $segmentData */
            $segmentData = &$current[$segment];
            $current = &$segmentData;
        }

        $current[$segments[\count($segments) - 1]] = $value;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function resolveProperty(string $type, mixed $data, string $locale, ItemMetadata $metadata, array $params = []): ContentView
    {
        $propertyResolver = $this->propertyResolverProvider->getPropertyResolver($type);

        if ($propertyResolver instanceof PropertyResolverMetadataAwareInterface && $metadata instanceof FieldMetadata) {
            return $propertyResolver->resolve($data, $locale, $params, $metadata);
        }

        return $propertyResolver->resolve($data, $locale, $params);
    }

    /**
     * @return array<string, string|int|mixed[]|bool|null>
     */
    private function serializeFieldMetadataOptions(ItemMetadata $metadata): array
    {
        $parameters = [];
        if (!$metadata instanceof FieldMetadata) {
            return [];
        }

        foreach ($metadata->getOptions() as $option) {
            $parameters[(string) $option->getName()] = $this->serializeOptionMetadata($option);
        }

        return $parameters;
    }

    /**
     * @return array<string|int, mixed>|string|int|bool|null
     */
    private function serializeOptionMetadata(OptionMetadata $metadata): string|int|array|bool|null
    {
        if (OptionMetadata::TYPE_COLLECTION === $metadata->getType()) {
            $values = [];
            /** @var OptionMetadata[]|null $metadataValues */
            $metadataValues = $metadata->getValue();
            if (!\is_array($metadataValues)) {
                throw new \InvalidArgumentException(
                    \sprintf('The value of option "%s" from type %s, must be an array, %s given.', $metadata->getName(), $metadata->getType(), \gettype($metadataValues)),
                );
            }
            foreach ($metadataValues as $option) {
                $name = $option->getName();
                $values[\is_int($name) ? $name : ((string) $name)] = $this->serializeOptionMetadata($option);
            }

            return $values;
        }

        /** @var string|int|bool|null $result */
        $result = $metadata->getValue() ?? $metadata->getName();

        return $result;
    }
}
