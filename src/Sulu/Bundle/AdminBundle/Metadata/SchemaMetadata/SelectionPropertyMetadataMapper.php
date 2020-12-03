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

use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;

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

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        $mandatory = $propertyMetadata->isRequired();
        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue($propertyMetadata);

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

        return new PropertyMetadata($propertyMetadata->getName(), $mandatory, $selectionMetadata);
    }
}
