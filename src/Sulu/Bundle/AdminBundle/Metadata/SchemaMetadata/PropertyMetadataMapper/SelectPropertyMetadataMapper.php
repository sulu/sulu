<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class SelectPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $selectMetadata = new AnyOfsMetadata([
            new StringMetadata(),
            new NumberMetadata(),
        ]);

        if (!$mandatory) {
            $selectMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $selectMetadata,
            ]);
        }

        return new PropertyMetadata((string) $fieldMetadata->getName(), $mandatory, $selectMetadata);
    }
}
