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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\ObjectMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class TeaserSelectionPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function __construct(
        private PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver,
    ) {
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($fieldMetadata);

        $itemsMetadata = new ArrayMetadata(
            new ObjectMetadata([
                new PropertyMetadata('id', true, new AnyOfsMetadata([
                    new StringMetadata(),
                    new NumberMetadata(),
                ])),
                new PropertyMetadata('type', true, new StringMetadata()),
                new PropertyMetadata('title', false, new StringMetadata()),
                new PropertyMetadata('description', false, new StringMetadata()),
                new PropertyMetadata('mediaId', false, new NumberMetadata()),
            ]),
            $minMaxValue->min,
            $minMaxValue->max,
            true
        );

        if (!$mandatory) {
            $itemsMetadata = new AnyOfsMetadata([
                new EmptyArrayMetadata(),
                $itemsMetadata,
            ]);
        }

        $teaserSelectionMetadata = new ObjectMetadata([
            new PropertyMetadata('items', $mandatory, $itemsMetadata),
            new PropertyMetadata('presentAs', false, new StringMetadata()),
        ]);

        if (!$mandatory) {
            $teaserSelectionMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $teaserSelectionMetadata,
            ]);
        }

        return new PropertyMetadata($fieldMetadata->getName(), $mandatory, $teaserSelectionMetadata);
    }
}
