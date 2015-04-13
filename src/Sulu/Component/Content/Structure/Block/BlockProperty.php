<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure\Block;

use JMS\Serializer\Annotation\HandlerCallback;
use JMS\Serializer\Annotation\Type;
use JMS\Serializer\Context;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use Sulu\Component\Content\Structure\Property;

/**
 * Block properties allow a choice from sets of properties - or "blocks"
 *
 * @Discriminator(disabled=true)
 */
class BlockProperty extends Property
{
    /**
     * properties managed by this block
     * @var BlockPropertyType[]
     * @Type("array<string,Sulu\Component\Content\Structure\Block\BlockPropertyType>")
     */
    private $types = array();

    /**
     * @var BlockPropertyType[]
     * @Type("array<integer,Sulu\Component\Content\Structure\Block\BlockPropertyType>")
     */
    private $properties = array();

    /**
     * @var string
     * @Type("string")
     */
    private $defaultTypeName;

    /**
     * returns a list of properties managed by this block
     * @return BlockPropertyType[]
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
     * @throws \InvalidArgumentException
     * @return BlockPropertyType
     */
    public function getBlockType($name)
    {
        if (!isset($this->types[$name])) {
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
     * return default type name
     * @return string
     */
    public function getDefaultTypeName()
    {
        return $this->defaultTypeName;
    }

    /**
     * returns child properties of given Type
     * @param string $typeName
     * @return PropertyInterface[]
     */
    public function getChildProperties($typeName)
    {
        return $this->getBlockType($typeName)->getChildProperties();
    }

    /**
     * initiate new child with given type name
     * @param integer $index
     * @param string $typeName
     * @return BlockPropertyType
     */
    public function initProperties($index, $typeName)
    {
        $type = $this->getBlockType($typeName);
        $this->properties[$index] = clone($type);

        return $this->properties[$index];
    }

    /**
     * clears all initialized properties
     */
    public function clearProperties()
    {
        $this->properties = array();
    }

    /**
     * returns properties for given index
     * @param integer $index
     * @return BlockPropertyType
     */
    public function getProperties($index)
    {
        if (!isset($this->properties[$index])) {
            throw new \OutOfRangeException(sprintf(
                'No index "%s" in properties: %s', $index, print_r($this->properties, true)
            ));
        }
        return $this->properties[$index];
    }

    /**
     * Returns sizeof block
     * @return int
     */
    public function getLength()
    {
        return sizeof($this->properties);
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
