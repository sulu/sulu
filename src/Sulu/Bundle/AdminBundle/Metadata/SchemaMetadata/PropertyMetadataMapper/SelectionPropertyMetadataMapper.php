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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\EmptyArrayMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class SelectionPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function __construct(private PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver)
    {
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();
        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $selectionMetadata = new ArrayMetadata(
            new AnyOfsMetadata([
                new StringMetadata(),
                new NumberMetadata(),
            ]),
            $minMaxValue->min,
            $minMaxValue->max,
            true
        );

        if (!$mandatory) {
            $selectionMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                new EmptyArrayMetadata(),
                $selectionMetadata,
            ]);
        }

        return new PropertyMetadata($fieldMetadata->getName(), $mandatory, $selectionMetadata);
    }
}
