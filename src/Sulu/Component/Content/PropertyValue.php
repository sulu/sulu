<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

/**
 * Class PropertyValue
 * @package Sulu\Component\Content
 */
class PropertyValue implements PropertyValueInterface
{
    /**
     * @var Metadata
     */
    private $meta;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $children = array();

    /**
     * {@inheritDoc}
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * {@inheritDoc}
     */
    public function setMeta(Metadata $meta)
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($name)
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function addChildren(PropertyValueInterface $value)
    {
        $this->children[] = $value;
        return $this;
    }
} 
