<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FieldMetadata;

class SelectionPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    /**
     * @var PropertyMetadataMinMaxValueResolver
     */
    private $propertyMetadataMinMaxValueResolver;

    public function __construct(PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver)
    {
        $this->propertyMetadataMinMaxValueResolver = $propertyMetadataMinMaxValueResolver;
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
