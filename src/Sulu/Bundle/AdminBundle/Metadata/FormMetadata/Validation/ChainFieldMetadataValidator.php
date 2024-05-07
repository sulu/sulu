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

class ChainFieldMetadataValidator implements FieldMetadataValidatorInterface
{
    /**
     * @param iterable<FieldMetadataValidatorInterface> $validators
     */
    public function __construct(private iterable $validators)
    {
    }

    public function validate(FieldMetadata $fieldMetadata, string $formKey): void
    {
        /** @var FieldMetadataValidatorInterface $validator */
        foreach ($this->validators as $validator) {
            $validator->validate($fieldMetadata, $formKey);
        }
    }
}
