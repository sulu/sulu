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
     * @return ContentView[]
     */
    public function resolveItems(array $items, array $data, string $locale): array
    {
        $contentViews = [];
        foreach ($items as $key => $item) {
            $name = $key;
            $type = $item->getType();
            if ($item instanceof SectionMetadata) {
                $contentViews = \array_merge(
                    $contentViews,
                    $this->resolveItems($item->getItems(), $data, $locale)
                );
            } else {
                $params = $item instanceof FieldMetadata ? $this->serializeFieldMetadataOptions($item) : [];
                $contentViews[$name] = $this->resolveProperty($type, $data[$name] ?? null, $locale, $item, $params);
            }
        }

        return $contentViews;
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
                $values[$option->getName()] = $this->serializeOptionMetadata($option);
            }

            return $values;
        }

        /** @var string|int|bool|null $result */
        $result = $metadata->getValue() ?? $metadata->getName();

        return $result;
    }
}
