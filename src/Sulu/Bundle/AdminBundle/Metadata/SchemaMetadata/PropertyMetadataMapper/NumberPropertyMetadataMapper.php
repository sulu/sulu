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
use Webmozart\Assert\Assert;

/**
 * @internal use symfony dependency injection container to override the service if you want to change the behavior
 */
final readonly class NumberPropertyMetadataMapper implements PropertyMetadataMapperInterface
{
    public function mapPropertyMetadata(FieldMetadata $fieldMetadata): PropertyMetadata
    {
        $propertyName = $fieldMetadata->getName();
        $mandatory = $fieldMetadata->isRequired();

        $min = $this->getFloatParam($fieldMetadata, 'min');
        $max = $this->getFloatParam($fieldMetadata, 'max');
        $multipleOf = $this->getFloatParam($fieldMetadata, 'multiple_of');
        $step = $this->getFloatParam($fieldMetadata, 'step');

        Assert::nullOrGreaterThan($multipleOf, 0, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than "0"',
            'multiple_of',
            $propertyName
        ));

        Assert::nullOrGreaterThan($step, 0, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than "0"',
            'step',
            $propertyName
        ));

        if (null !== $step && null !== $multipleOf) {
            if (!$this->isMultipleOf($step, $multipleOf)) {
                throw new \RuntimeException(
                    \sprintf(
                        'Because parameter "%1$s" of property "%2$s" has value "%4$s", parameter "%3$s" needs to be a multiple of "%4$s"',
                        'multiple_of',
                        $propertyName,
                        'step',
                        \strval($multipleOf)
                    )
                );
            }
        }

        if (null !== $min && null !== $max) {
            Assert::greaterThanEq($max, $min, \sprintf(
                'Because parameter "%1$s" of property "%2$s" has value "%4$s", parameter "%3$s" needs to be greater than or equal "%4$s"',
                'min',
                $propertyName,
                'max',
                \strval($min)
            ));
        }

        $numberMetadata = new NumberMetadata(
            $min,
            $max,
            $multipleOf
        );

        if (!$mandatory) {
            $numberMetadata = new AnyOfsMetadata([
                new NullMetadata(),
                $numberMetadata,
            ]);
        }

        return new PropertyMetadata(
            $propertyName,
            $mandatory,
            $numberMetadata
        );
    }

    // Cannot use fmod($value, $multipleOf) here, because fmod(1, 0.01) returns 0.09999999999999995 instead of 0
    private function isMultipleOf(float $value, float $multipleOf): bool
    {
        return 0.0 === $value - (int) (\floor($value / $multipleOf) * $multipleOf);
    }

    private function getFloatParam(FieldMetadata $fieldMetadata, string $paramName): ?float
    {
        $value = $fieldMetadata->findOption($paramName)?->getValue();

        if (null === $value) {
            return null;
        }

        Assert::numeric($value, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or numeric',
            $paramName,
            $fieldMetadata->getName()
        ));

        return (float) $value;
    }
}
