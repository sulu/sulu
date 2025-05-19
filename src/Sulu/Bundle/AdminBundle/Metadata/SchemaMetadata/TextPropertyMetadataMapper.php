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

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $minMaxValue = (object) [
            'min' => null,
            'max' => null,
        ];

        if (null !== $this->propertyMetadataMinMaxValueResolver) {
            $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
                $fieldMetadata,
                'min_length',
                'max_length'
            );
        }

        $pattern = $fieldMetadata->findOption('pattern')?->getValue();
        \assert(\is_string($pattern) || null === $pattern, 'The option "pattern" must be a string or null.');

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
            $fieldMetadata->getName(),
            $fieldMetadata->isRequired(),
            $textLineMetadata
        );
    }
}
