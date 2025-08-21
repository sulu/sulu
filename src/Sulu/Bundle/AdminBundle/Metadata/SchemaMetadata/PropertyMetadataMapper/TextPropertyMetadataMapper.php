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
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\EmptyStringMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMinMaxValueResolver;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\StringMetadata;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class TextPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function __construct(
        private PropertyMetadataMinMaxValueResolver $propertyMetadataMinMaxValueResolver
    ) {
    }

    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $mandatory = $fieldMetadata->isRequired();

        $minMaxValue = $this->propertyMetadataMinMaxValueResolver->resolveMinMaxValue(
            $fieldMetadata,
            'min_length',
            'max_length'
        );

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
