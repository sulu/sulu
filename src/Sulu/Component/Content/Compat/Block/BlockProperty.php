<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat\Block;

use Sulu\Component\Content\Compat\Property;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;

/**
 * interface definition for block property.
 *
 * @method BlockPropertyType[] getTypes()
 * @method addType(BlockPropertyType $type)
 * @method BlockPropertyType getType(string $name)
 * @method BlockPropertyType getProperties(int $index)
 * @method BlockPropertyType initProperties(int $index, string $typeName)
 */
class BlockProperty extends Property implements BlockPropertyInterface
{
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
            $col,
            $defaultTypeName
        );
    }

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
        if (\array_keys($items) !== \range(0, \count($items) - 1)) {
            $items = [$items];
        }

        $this->properties = [];

        for ($i = 0; $i < \count($items); ++$i) {
            $item = $items[$i];
            $type = $this->initProperties($i, $item['type']);
            if (isset($item['settings'])) {
                $type->setSettings($item['settings']);
            }

            /** @var PropertyInterface $subProperty */
            foreach ($type->getChildProperties() as $subProperty) {
                if (!isset($item[$subProperty->getName()])) {
                    continue;
                }

                $subName = $subProperty->getName();
                $subValue = $item[$subName];

                if ($subValue instanceof PropertyValue) {
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
        if (\count($this->properties) < $this->getMinOccurs()) {
            for ($i = \count($this->properties); $i < $this->getMinOccurs(); ++$i) {
                $this->initProperties($i, $this->getDefaultTypeName());
            }
        }

        $data = [];
        foreach ($this->properties as $type) {
            $result = [
                'type' => $type->getName(),
                'settings' => $type->getSettings(),
            ];
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
        if (\is_null($this->getMinOccurs()) || \is_null($this->getMaxOccurs())) {
            // in contrast to properties blocks are multiple by default
            return true;
        }

        return parent::getIsMultiple();
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
        return $this->getTypeChildProperties($typeName);
    }
}
