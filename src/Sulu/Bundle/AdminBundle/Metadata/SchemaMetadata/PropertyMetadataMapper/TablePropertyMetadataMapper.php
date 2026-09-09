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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SchemaMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class TablePropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $cellMetadata = new ObjectMetadata([
            new PropertyMetadata('text', true, new StringMetadata()),
            new PropertyMetadata('bold', false, new SchemaMetadata()),
            new PropertyMetadata('italic', false, new SchemaMetadata()),
            new PropertyMetadata('underline', false, new SchemaMetadata()),
        ]);

        $tableMetadata = new ObjectMetadata([
            new PropertyMetadata('version', false, new NumberMetadata()),
            new PropertyMetadata('head', false, new ArrayMetadata(new StringMetadata())),
            new PropertyMetadata('body', false, new ArrayMetadata(
                new ArrayMetadata(new AnyOfsMetadata([
                    new StringMetadata(),
                    $cellMetadata,
                ]))
            )),
            new PropertyMetadata('options', false, new SchemaMetadata()),
        ]);

        if (!$mandatory) {
            $tableMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $tableMetadata,
            ]);
        }

        return new PropertyMetadata($fieldMetadata->getName(), $mandatory, $tableMetadata);
    }
}
