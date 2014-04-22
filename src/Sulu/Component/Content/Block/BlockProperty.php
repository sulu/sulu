<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Block;

use Sulu\Component\Content\Property;
use Sulu\Component\Content\PropertyInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * representation of a block node in template xml
 */
class BlockProperty extends Property implements BlockPropertyInterface
{
    /**
     * properties managed by this block
     * @var BlockPropertyType[]
     */
    private $types = array();

    function __construct(
        $name,
        $title,
        $mandatory = false,
        $multilingual = false,
        $maxOccurs = 1,
        $minOccurs = 1,
        $params = array()
    )
    {
        parent::__construct($name, $title, 'block', $mandatory, $multilingual, $maxOccurs, $minOccurs, $params);
    }

    /**
     * returns a list of properties managed by this block
     * @return PropertyInterface[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * adds a type
     * @param BlockPropertyType $type
     */
    public function addType(BlockPropertyType $type)
    {
        $this->types[$type->getName()] = $type;
    }

    /**
     * returns type with given name
     * @param string $name of property
     * @return BlockPropertyType
     */
    public function getType($name)
    {
        return $this->types[$name];
    }

    /**
     * returns child properties of given Type
     * @param string $typeName
     * @return PropertyInterface[]
     */
    public function getChildProperties($typeName)
    {
        return $this->getType($typeName)->getChildProperties();
    }

    /**
     * set value of child properties
     * @param mixed $value
     */
    public function setValue($value)
    {
        if ($value != null) {
            $data = array();
            // check value for associativeness
            if (!(array_keys($value) !== range(0, count($value) - 1))) {
                foreach ($value as $item) {
                    foreach ($item as $key => $itemValue) {
                        if (!isset($data[$key])) {
                            $data[$key] = array();
                        }
                        $data[$key][] = $itemValue;
                    }
                }
            } else {
                $data = $value;
            }
            /** @var PropertyInterface $subProperty */
            foreach ($this->types as $subProperty) {
                if (isset($data[$subProperty->getName()])) {
                    $subProperty->setValue($data[$subProperty->getName()]);
                }
            }
        }
    }

    /**
     * get value of sub properties
     * @return array|mixed
     */
    public function getValue()
    {
        $data = array();
        if ($this->getIsMultiple()) {
            /** @var PropertyInterface $child */
            foreach ($this->types as $child) {
                $items = $child->getValue();
                if ($items !== null) {
                    // check value is not associative
                    if (!(array_keys($items) !== range(0, count($items) - 1))) {
                        foreach ($items as $key => $item) {
                            $data[$key][$child->getName()] = $item;
                        }
                    } else {
                        // go thrue associative array
                        foreach ($items as $varName => $item) {
                            foreach ($item as $key => $itemValue) {
                                $data[$key][$child->getName()][$varName] = $itemValue;
                            }
                        }
                    }
                }
            }
        } else {
            /** @var PropertyInterface $child */
            foreach ($this->types as $child) {
                $data[$child->getName()] = $child->getValue();
            }
        }
        return $data;
    }

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock()
    {
        return true;
    }


}
