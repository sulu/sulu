<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;

use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\AnyOfsMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NullMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\NumberMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadata;
use Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\PropertyMetadataMapperInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata as ContentPropertyMetadata;
use Sulu\Component\Content\SimpleContentType;
use Webmozart\Assert\Assert;

/**
 * ContentType for Number.
 */
class Number extends SimpleContentType implements PropertyMetadataMapperInterface
{
    public function __construct()
    {
        parent::__construct('Number');
    }

    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if (\is_numeric($value)) {
            $node->setProperty(
                $property->getName(),
                $this->removeIllegalCharacters($this->encodeValue($value)),
                PropertyType::DOUBLE
            );
        } else {
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }
    }

    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $value = $this->defaultValue;
        if ($node->hasProperty($property->getName())) {
            $value = $node->getPropertyValue($property->getName(), PropertyType::DOUBLE);
        }

        $property->setValue($this->decodeValue($value));

        return $value;
    }

    public function mapPropertyMetadata(ContentPropertyMetadata $propertyMetadata): PropertyMetadata
    {
        /** @var string $propertyName */
        $propertyName = $propertyMetadata->getName();
        $mandatory = $propertyMetadata->isRequired();

        $min = $this->getFloatParam($propertyMetadata, 'min');
        $max = $this->getFloatParam($propertyMetadata, 'max');
        $multipleOf = $this->getFloatParam($propertyMetadata, 'multiple_of');
        $step = $this->getFloatParam($propertyMetadata, 'step');

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

    private function getFloatParam(ContentPropertyMetadata $propertyMetadata, string $paramName): ?float
    {
        /** @var mixed[]|null $param */
        $param = $propertyMetadata->getParameter($paramName);
        $value = $param['value'] ?? null;

        if (null === $value) {
            return null;
        }

        Assert::numeric($value, \sprintf(
            'Parameter "%s" of property "%s" needs to be either null or numeric',
            $paramName,
            $propertyMetadata->getName()
        ));

        return (float) $value;
    }
}
