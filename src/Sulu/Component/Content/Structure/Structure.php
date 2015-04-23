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
     * Same as $items but without Sections
     */
    public $modelProperties;

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
        return $this->getChild($name);
    }

    /**
     * Return a model property
     *
     * We keep two representations of the property data, one with
     * Sections and one without.
     */
    public function getModelProperty($name)
    {
        if (!isset($this->modelProperties[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown model property "%s", in structure "%s". Known model properties: "%s". Loaded from "%s"',
                $name, $this->getName(), implode('", "', array_keys($this->modelProperties)),
                $this->resource
            ));
        }

        return $this->modelProperties[$name];
    }

    public function getModelProperties()
    {
        return $this->modelProperties;
    }

    /**
     * Create a copy of the properties excluding the section's
     *
     * Should only be called before writing to the cache.
     *
     * TODO: Do not use get|setProperties, use get|setChildren
     */
    public function burnModelRepresentation()
    {
        $this->modelProperties = $this->getProperties(true);
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty($name)
    {
        return $this->hasChild($name);
    }

    /**
     * TODO: Implement highest
     *
     * {@inheritDoc}
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        $properties = $this->getPropertiesByTagName($tagName);

        if (!$properties) {
            throw new \InvalidArgumentException(sprintf(
                'No property with tag "%s" exists. In structure "%s" loaded from "%s"',
                $tagName, $this->name, $this->resource
            ));
        }

        return reset($properties);
    }

    public function hasPropertyWithTagName($tagName)
    {
        return (boolean) count($this->getPropertiesByTagName($tagName));
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
     * Return the resource from which this structure was loaded
     *
     * @return string
     */
    public function getResource() 
    {
        return $this->resource;
    }
}
