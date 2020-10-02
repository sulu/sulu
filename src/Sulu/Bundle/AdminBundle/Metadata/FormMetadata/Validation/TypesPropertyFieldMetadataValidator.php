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

class TypesPropertyFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    public function validate(FieldMetadata $fieldMetadata, string $formKey): void
    {
        foreach ($fieldMetadata->getTypes() as $type) {
            $this->validateItems($fieldMetadata, $type->getItems(), $formKey);
        }
    }

    /**
     * @param ItemMetadata[] $items
     *
     * @throws ReservedPropertyNameException
     */
    private function validateItems(FieldMetadata $fieldMetadata, array $items, string $formKey): void
    {
        foreach ($items as $itemMetadata) {
            if ($itemMetadata instanceof SectionMetadata) {
                $this->validateItems($fieldMetadata, $itemMetadata->getItems(), $formKey);
            }

            if ($itemMetadata instanceof FieldMetadata) {
                $this->validateField($fieldMetadata, $itemMetadata, $formKey);
            }
        }
    }

    /**
     * @throws ReservedPropertyNameException
     */
    private function validateField(FieldMetadata $fieldMetadata, FieldMetadata $propertyMetadata, string $formKey): void
    {
        $propertyName = $propertyMetadata->getName();

        if ('type' === $propertyName) {
            throw new ReservedPropertyNameException(
                $fieldMetadata->getName(),
                $propertyName,
                $formKey
            );
        }
    }
}
