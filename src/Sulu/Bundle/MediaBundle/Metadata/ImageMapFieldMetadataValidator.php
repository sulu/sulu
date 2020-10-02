<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Metadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation\FieldMetadataValidatorInterface;
use Sulu\Component\Content\Exception\ReservedPropertyNameException;

class ImageMapFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    public function validate(FieldMetadata $fieldMetadata, string $formKey): void
    {
        if ('image_map' !== $fieldMetadata->getType()) {
            return;
        }

        foreach ($fieldMetadata->getTypes() as $type) {
            $this->validateItems($fieldMetadata, $type->getItems(), $formKey);
        }
    }

    /**
     * @param ItemMetadata[] $items
     *
     * @throws ReservedPropertyNameException
     */
    private function validateItems(FieldMetadata $imageMapMetadata, array $items, string $formKey): void
    {
        foreach ($items as $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                $this->validateItems($imageMapMetadata, $itemMetadata->getItems(), $formKey);
            }

            if ($itemMetadata instanceof FieldMetadata) {
                $this->validateField($imageMapMetadata, $itemMetadata, $formKey);
            }
        }
    }

    /**
     * @throws ReservedPropertyNameException
     */
    private function validateField(FieldMetadata $imageMapMetadata, FieldMetadata $propertyMetadata, string $formKey): void
    {
        $propertyName = $propertyMetadata->getName();

        if ('hotspot' === $propertyName) {
            throw new ReservedPropertyNameException(
                $imageMapMetadata->getName(),
                $propertyName,
                $formKey
            );
        }
    }
}
