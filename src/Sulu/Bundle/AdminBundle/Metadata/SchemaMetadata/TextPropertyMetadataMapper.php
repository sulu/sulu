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

class TextPropertyMetadataMapper implements PropertyMetadataMapperInterface
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

        $minMaxValue = (object) [
            'min' => null,
            'max' => null,
        ];

        if (null !== $this->propertyMetadataMinMaxValueResolver) {
            $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
                $propertyMetadata,
                'minLength',
                'maxLength'
            );
        }

        $pattern = $propertyMetadata->getParameter('pattern')['value'] ?? null;

        $textLineMetadata = new StringMetadata(
            $minMaxValue->min,
            $minMaxValue->max,
            $pattern
        );

        if (!$mandatory) {
            $textLineMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                new EmptyStringMetadata(),
                $textLineMetadata,
            ]);
        }

        return new PropertyMetadata(
            $propertyMetadata->getName(),
            $propertyMetadata->isRequired(),
            $textLineMetadata
        );
    }
}
