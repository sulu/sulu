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
use Webmozart\Assert\Assert;

class PropertyMetadataMinMaxValueResolver
{
    /**
     * Result object has following structure:
     * <code>
     * ?int $min - value of min parameter
     * ?int $max - value of max parameter
     * </code>.
     *
     * @return object{
     *     min: int|null,
     *     max: int|null,
     * }
     *
     * @throws \InvalidArgumentException
     */
    public function resolveMinMaxValue(
        FieldMetadata $fieldMetadata,
        string $minParamName = 'min',
        string $maxParamName = 'max'
    ): object {
        $mandatory = $fieldMetadata->isRequired();

        $min = $fieldMetadata->findOption($minParamName)?->getValue();
        Assert::nullOrIntegerish($min, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or of type int',
            $minParamName,
            $fieldMetadata->getName()
        ));

        if (null !== $min) {
            $min = (int) $min;
        }

        Assert::nullOrGreaterThanEq($min, 0, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than or equal "0"',
            $minParamName,
            $fieldMetadata->getName()
        ));

        if ($mandatory) {
            if (null === $min) {
                $min = 1;
            }

            Assert::greaterThanEq($min, 1, \sprintf(
                'Because property "%s" is mandatory, parameter "%s" needs to be greater than or equal "1"',
                $fieldMetadata->getName(),
                $minParamName
            ));
        }

        $max = $fieldMetadata->findOption($maxParamName)?->getValue();
        Assert::nullOrIntegerish($max, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or of type int',
            $maxParamName,
            $fieldMetadata->getName()
        ));

        if (null !== $max) {
            $max = (int) $max;
        }

        Assert::nullOrGreaterThanEq($max, 1, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than or equal "1"',
            $maxParamName,
            $fieldMetadata->getName()
        ));

        if (null !== $min) {
            Assert::nullOrGreaterThanEq($max, $min, \sprintf(
                'Because parameter "%1$s" of property "%2$s" has value "%4$d", parameter "%3$s" needs to be greater than or equal "%4$d"',
                $minParamName,
                $fieldMetadata->getName(),
                $maxParamName,
                $min
            ));
        }

        return (object) [
            'min' => $min,
            'max' => $max,
        ];
    }
}
