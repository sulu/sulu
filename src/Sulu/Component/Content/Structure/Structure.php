<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Structure;

use Sulu\Component\Content\Structure\Property;
use Sulu\Component\Content\Structure\Section;
use Sulu\Component\Content\Exception\NoSuchPropertyException;

class Structure extends Item
{
    /**
     * The resource from which this structure was loaded
     * (useful for debugging)
     *
     * @var string
     */
    public $resource;

    /**
     * TODO: This should be an option as it is implementation specific
     * @var string
     */
    public $cacheLifetime;

    /**
     * TODO: This should be an option as it is implementation specific
     * @var string
     */
    public $controller;

    /**
     * TODO: This should be an option as it is implementation specific
     * @var string
     */
    public $view;

    /**
     * Return all direct child properties of this structure, ignoring
     * Sections
     *
     * @return Property[]
     */
    public function getProperties($flatten = false)
    {
        if (false === $flatten) {
            return $this->children;
        }

        $properties = array();
        foreach ($this->children as $child) {
            if ($child instanceof Section) {
                $properties = array_merge($properties, $child->getChildren());
                continue;
            }

            $properties[$child->name] = $child;
        }

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty($name)
    {
        if (!isset($this->children[$name])) {
            throw new NoSuchPropertyException($this->name, sprintf(
                'Property "%s" does not exist in structure "%s" loaded from resource "%s"',
                $name,
                $this->name,
                $this->resource
            ));
        }

        return $this->children[$name];
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return isset($this->children[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyNames()
    {
        return array_keys($this->children);
    }

    /**
     * TODO: Implement this
     *
     * {@inheritDoc}
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        $properties = $this->getPropertiesByTagName($tagName);
        return reset($properties);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = array();

        foreach ($this->getProperties() as $property) {
            foreach ($property->tags as $tag) {
                if ($tag['name'] == $tagName){
                    $properties[$property->name] = $property;
                }
            }
        }

        return $properties;
    }

    /**
     * {@inheritDoc}
     */
    public function hasTag($tagName)
    {
        return (boolean) $this->getPropertiesByTagName($tagName);
    }
}
