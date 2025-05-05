<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata;

use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperRegistry;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;

/**
 * @internal this class is not part of the public API and may be changed or removed without further notice
 */
class SchemaMetadataProvider
{
    public function __construct(private PropertyMetadataMapperRegistry $propertyMetadataMapperRegistry)
    {
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    public function getMetadata(array $itemsMetadata): SchemaMetadata
    {
        return new SchemaMetadata($this->getSchemaProperties($itemsMetadata));
    }

    /**
     * @param ItemMetadata[] $itemsMetadata
     */
    private function getSchemaProperties(array $itemsMetadata): array
    {
        return \array_filter(\array_map(function(ItemMetadata $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                return $this->getSchemaProperties($itemMetadata->getItems());
            }

            \assert($itemMetadata instanceof FieldMetadata, 'ItemMetadata is expected to be FieldMetadata');

            $type = $itemMetadata->getType();

            if ($this->propertyMetadataMapperRegistry->has($type)) {
                return $this->propertyMetadataMapperRegistry
                    ->get($type)
                    ->mapPropertyMetadata($itemMetadata);
            }

            return new PropertyMetadata(
                $itemMetadata->getName(),
                $itemMetadata->isRequired()
            );
        }, $itemsMetadata));
    }
}
