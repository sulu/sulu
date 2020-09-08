<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\ItemMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\SectionMetadata;
use Sulu\Component\Content\Exception\ReservedPropertyNameException;

class BlockFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    const RESERVED_PROPERTY_NAMES = ['type', 'settings'];

    public function validate(FieldMetadata $fieldMetadata, string $formKey): void
    {
        if ('block' !== $fieldMetadata->getType()) {
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
    private function validateItems(FieldMetadata $blockMetadata, array $items, string $formKey): void
    {
        foreach ($items as $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                $this->validateItems($blockMetadata, $itemMetadata->getItems(), $formKey);
            }

            if ($itemMetadata instanceof FieldMetadata) {
                $this->validateField($blockMetadata, $itemMetadata, $formKey);
            }
        }
    }

    /**
     * @throws ReservedPropertyNameException
     */
    private function validateField(FieldMetadata $blockMetadata, FieldMetadata $fieldMetadata, string $formKey): void
    {
        $propertyName = $fieldMetadata->getName();

        if (\in_array($propertyName, static::RESERVED_PROPERTY_NAMES, true)) {
            throw new ReservedPropertyNameException(
                $blockMetadata->getName(),
                $propertyName,
                $formKey
            );
        }
    }
}
