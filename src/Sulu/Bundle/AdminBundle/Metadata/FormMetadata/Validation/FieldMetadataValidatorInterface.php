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

interface FieldMetadataValidatorInterface
{
    /**
     * @throws InvalidFieldMetadataException
     */
    public function validate(FieldMetadata $fieldMetadata): void;
}
