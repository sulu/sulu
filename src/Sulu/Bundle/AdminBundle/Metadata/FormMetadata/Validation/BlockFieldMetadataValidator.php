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

namespace Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Validation;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;

class BlockFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    const RESERVED_PROPERTY_NAMES = ['type', 'settings'];

    public function validate(FieldMetadata $fieldMetadata): void
    {
        if ('block' !== $fieldMetadata->getType()) {
            return;
        }

        foreach ($fieldMetadata->getTypes() as $type) {
            foreach ($type->getItems() as $item) {
                if (!$item instanceof FieldMetadata) {
                    continue;
                }

                if (\in_array($item->getName(), static::RESERVED_PROPERTY_NAMES, true)) {
                    throw new InvalidFieldMetadataException(
                        \sprintf(
                            'Block "%s" has a child property named "%s", although it is a reserved property name',
                            $fieldMetadata->getName(),
                            $item->getName()
                        )
                    );
                }
            }
        }
    }
}
