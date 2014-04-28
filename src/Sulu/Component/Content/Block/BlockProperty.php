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

    /**
     * @var array
     */
    private $properties = array();

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
        $this->currentTypeName = null;
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
     * initiate new child with given type name
     * @param integer $index
     * @param string $typeName
     * @return PropertyInterface[]
     */
    public function initProperties($index, $typeName)
    {
        $this->properties[$index] = array('type' => $typeName);

        /** @var PropertyInterface $subProperty */
        foreach ($this->getChildProperties($typeName) as $subProperty) {
            $this->properties[$index][$subProperty->getName()] = clone($subProperty);
        }

        return $this->properties[$index];
    }

    /**
     * returns properties for given index
     * @param integer $index
     * @return PropertyInterface[]
     */
    public function getProperties($index)
    {
        return $this->properties[$index];
    }

    /**
     * set value of child properties
     * @param mixed $value
     */
    public function setValue($value)
    {
        if ($value != null) {
            // check value for single value
            if (array_keys($value) !== range(0, count($value) - 1)) {
                $value = array($value);
            }

            $this->properties = array();
            $len = count($value);
            for ($i = 0; $i < $len; $i++) {
                $item = $value[$i];
                $result = array('type' => $item['type']);

                /** @var PropertyInterface $subProperty */
                foreach ($this->getChildProperties($item['type']) as $subProperty) {
                    if (isset($item[$subProperty->getName()])) {
                        $subProperty->setValue($item[$subProperty->getName()]);
                        $result[$subProperty->getName()] = clone($subProperty);
                    }
                }
                $this->properties[] = $result;
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
        foreach ($this->properties as $value) {
            $result = array('type' => $value['type']);
            foreach ($value as $typeName => $contentType) {
                if ($typeName !== 'type') {
                    $result[$typeName] = $contentType->getValue();
                }
            }
            $data[] = $result;
        }

        if (!$this->getIsMultiple()) {
            return sizeof($data) > 0 ? $data[0] : null;
        } else {
            return $data;
        }
    }

    /**
     * returns TRUE if property is a block
     * @return boolean
     */
    public function getIsBlock()
    {
        return true;
    }

    function __clone()
    {
        $clone = new BlockProperty(
            $this->getName(),
            $this->getTitle(),
            $this->getMandatory(),
            $this->getMultilingual(),
            $this->getMaxOccurs(),
            $this->getMinOccurs(),
            $this->getParams()
        );

        $clone->types = array();
        foreach ($this->types as $type) {
            $clone->addType(clone($type));
        }

        $clone->setValue($this->getValue());
        return $clone;
    }
}
