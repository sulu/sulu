<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

class SelectionPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();
        $minItems = $propertyMetadata->getMinOccurs();
        $maxItems = $propertyMetadata->getMaxOccurs();

        if (null !== $minItems) {
            // If minOccurs is set, minItems is at least 0
            $minItems = \max(0, $minItems);
        }

        if ($mandatory) {
            // If mandatory, minItems is at least 1
            $minItems = \max(1, $minItems ?? 0);
        }

        if (null !== $maxItems) {
            // maxItems is at least 0 and at least as high as minItems
            $maxItems = \max($minItems ?? 0, $maxItems);
        }

        return new ArrayMetadata(
            $propertyMetadata->getName(),
            $mandatory,
            new SchemaMetadata([], [
                new SchemaMetadata([], [], [], 'string'),
                new SchemaMetadata([], [], [], 'number'),
            ]),
            $minItems,
            $maxItems,
            true
        );
    }
}
