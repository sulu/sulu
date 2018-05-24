<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;

/**
 * Representation of a block node in template xml.
 */
class BlockProperty extends Property implements BlockPropertyInterface
{
    /**
     * properties managed by this block.
     *
     * @var BlockPropertyType[]
     */
    private $types = [];

    /**
     * @var BlockPropertyType[]
     */
    private $properties = [];

    /**
     * @var string
     */
    private $defaultTypeName;

    public function __construct(
        $name,
        $metadata,
        $defaultTypeName,
        $mandatory = false,
        $multilingual = false,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = [],
        $tags = [],
        $col = null
    ) {
        parent::__construct(
            $name,
            $metadata,
            'block',
            $mandatory,
            $multilingual,
            $maxOccurs,
            $minOccurs,
            $params,
            $tags,
            $col
        );

        $this->defaultTypeName = $defaultTypeName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     */
    public function addType(BlockPropertyType $type)
    {
        $this->types[$type->getName()] = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!$this->hasType($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The block type "%s" has not been registered. Known block types are: [%s]',
                    $name,
                    implode(', ', array_keys($this->types))
                )
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultTypeName()
    {
        return $this->defaultTypeName;
    }

    /**
     * returns child properties of given Type.
     *
     * @param string $typeName
     *
     * @return PropertyInterface[]
     */
    public function getChildProperties($typeName)
    {
        return $this->getType($typeName)->getChildProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function initProperties($index, $typeName)
    {
        $type = $this->getType($typeName);
        $this->properties[$index] = clone $type;

        return $this->properties[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function clearProperties()
    {
        $this->properties = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($index)
    {
        if (!isset($this->properties[$index])) {
            throw new \OutOfRangeException(sprintf(
                'No properties at index "%s" in block "%s". Valid indexes: [%s]',
                $index, $this->getName(), implode(', ', array_keys($this->properties))
            ));
        }

        return $this->properties[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function getLength()
    {
        return count($this->properties);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value)
    {
        $this->doSetValue($value);

        if ($this->propertyValue) {
            $this->propertyValue->setValue($value);
        }
    }

    public function setPropertyValue(PropertyValue $value)
    {
        parent::setPropertyValue($value);
        $this->doSetValue($value);
    }

    /**
     * Sub properties need to be referenced to the PropertyValue so
     * that the "real" property is updated.
     *
     * TODO: This is very tedious code. It is important to factor this out.
     */
    public function doSetValue($value)
    {
        $items = $value;
        if ($value instanceof PropertyValue) {
            $items = $value->getValue();
        }

        if (null == $items) {
            return;
        }

        // check value for single value
        if (array_keys($items) !== range(0, count($items) - 1)) {
            $items = [$items];
        }

        $this->properties = [];

        for ($i = 0; $i < count($items); ++$i) {
            $item = $items[$i];
            $type = $this->initProperties($i, $item['type']);

            /** @var PropertyInterface $subProperty */
            foreach ($type->getChildProperties() as $subProperty) {
                if (!isset($item[$subProperty->getName()])) {
                    continue;
                }

                $subName = $subProperty->getName();
                $subValue = $item[$subName];

                if ($value instanceof PropertyValue) {
                    $subValueProperty = new PropertyValue($subName, $subValue);
                    $subProperty->setPropertyValue($subValueProperty);
                    $item[$subName] = $subValueProperty;
                } else {
                    $subProperty->setValue($subValue);
                }
            }

            $items[$i] = $item;
        }

        if ($value instanceof PropertyValue) {
            $value->setValue($items);
        }
    }

    /**
     * get value of sub properties.
     *
     * @return array|mixed
     */
    public function getValue()
    {
        // if size of children smaller than minimum
        if (count($this->properties) < $this->getMinOccurs()) {
            for ($i = count($this->properties); $i < $this->getMinOccurs(); ++$i) {
                $this->initProperties($i, $this->getDefaultTypeName());
            }
        }

        $data = [];
        foreach ($this->properties as $type) {
            $result = ['type' => $type->getName()];
            foreach ($type->getChildProperties() as $property) {
                $result[$property->getName()] = $property->getValue();
            }
            $data[] = $result;
        }

        return $data;
    }

    /**
     * returns TRUE if property is a block.
     *
     * @return bool
     */
    public function getIsBlock()
    {
        return true;
    }

    public function getIsMultiple()
    {
        if (is_null($this->getMinOccurs()) || is_null($this->getMaxOccurs())) {
            // in contrast to properties blocks are multiple by default
            return true;
        }

        return parent::getIsMultiple();
    }

    public function __clone()
    {
        $clone = new self(
            $this->getName(),
            $this->getMetadata(),
            $this->getDefaultTypeName(),
            $this->getMandatory(),
            $this->getMultilingual(),
            $this->getMaxOccurs(),
            $this->getMinOccurs(),
            $this->getParams()
        );

        $clone->types = [];
        foreach ($this->types as $type) {
            $clone->addType(clone $type);
        }

        $clone->setValue($this->getValue());

        return $clone;
    }
}
