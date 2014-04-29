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

use Sulu\Component\Content\PropertyInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * representation of a block type node in template xml
 */
class BlockPropertyType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $title;

    /**
     * properties managed by this block
     * @var PropertyInterface[]
     */
    private $childProperties = array();

    function __construct($name, $title)
    {
        $this->name = $name;
        $this->title = $title;
    }

    /**
     * returns a list of properties managed by this block
     * @return PropertyInterface[]
     */
    public function getChildProperties()
    {
        return $this->childProperties;
    }

    /**
     * @param PropertyInterface $property
     */
    public function addChild(PropertyInterface $property)
    {
        $this->childProperties[] = $property;
    }

    /**
     * returns property with given name
     * @param string $name of property
     * @throws NoSuchPropertyException
     * @return PropertyInterface
     */
    public function getChild($name)
    {
        foreach ($this->childProperties as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }
        throw new NoSuchPropertyException();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    function __clone()
    {
        $result = new BlockPropertyType($this->getName(), $this->getTitle());
        $result->childProperties = array();
        foreach ($this->getChildProperties() as $childProperties) {
            $result->addChild(clone($childProperties));
        }
        return $result;
    }
} 
