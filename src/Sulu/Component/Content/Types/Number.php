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

        Assert::nullOrGreaterThan($multipleOf, 0, \sprintf(
            'Parameter "%s" of property "%s" needs to be greater than "0"',
            'multiple_of',
            $propertyName
        ));

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

    private function getFloatParam(ContentPropertyMetadata $propertyMetadata, string $paramName): ?float
    {
        $value = $propertyMetadata->getParameter($paramName)['value'] ?? null;

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
