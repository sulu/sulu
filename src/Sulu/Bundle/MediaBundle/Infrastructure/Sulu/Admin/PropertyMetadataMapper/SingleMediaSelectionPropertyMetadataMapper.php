<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Admin\PropertyMetadataMapper;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class SingleMediaSelectionPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $idMetadata = new NumberMetadata();

        if (!$mandatory) {
            $idMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $idMetadata,
            ]);
        }

        $singleMediaSelectionMetadata = new ObjectMetadata([
            new PropertyMetadata('id', $mandatory, $idMetadata),
            new PropertyMetadata('displayOption', false, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $singleMediaSelectionMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $singleMediaSelectionMetadata,
            ]);
        }

        return new PropertyMetadata($fieldMetadata->getName(), $mandatory, $singleMediaSelectionMetadata);
    }
}
