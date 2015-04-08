<?php

/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Structure;

use DTL\Component\Content\Structure\Item;

class Item
{
    /**
     * Name of this item
     *
     * @var string
     */
    public $name;

    /**
     * The title of this property|structure e.g. [["de": "Artikles", "en": "Articles"]]
     *
     * @var array
     */
    public $title = array();

    /**
     * Description of this property|structure e.g. [["de": "Liste von Artikeln", "en": "List of articles"]]
     *
     * @var array
     */
    public $description = array();

    /**
     * Tags, e.g. [['name' => 'sulu_search.field', 'type' => 'string']]
     *
     * @var array
     */
    public $tags = array();

    /**
     * Parameters applying to the property
     *
     * e.g.
     *
     * {
     *     placeholder: Enter some text
     * }
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Children of this item, f.e. properties, sections or structures
     *
     * @var Item[]
     */
    public $children = array();

    /**
     * @param mixed $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Return the named property
     *
     * @return string $name
     */
    public function getChild($name)
    {
        if (!isset($this->children[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown child "%s" in structure "%s" loaded from: "%s". Children: "%s"',
                 $name, $this->name, $this->resource, implode('", "', array_keys($this->children))
            ));
        }

        return $this->children[$name];
    }

    /**
     * Return true if this structure has the named property, false
     * if it does not.
     *
     * @param string $name
     */
    public function hasChild($name)
    {
        return isset($this->children[$name]);
    }

    /**
     * Adds a child item
     *
     * @param Item $child
     */
    public function addChild(Item $child)
    {
        if (isset($this->children[$child->name])) {
            throw new \InvalidArgumentException(sprintf(
                'Child with key "%s" already exists',
                $child->name
            ));
        }

        $this->children[$child->name] = $child;
    }

    /**
     * Return the children of this item
     *
     * @return Item[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Magic _set to catch undefined property accesses
     */
    public function __set($name, $value)
    {
        throw new \InvalidArgumentException(sprintf(
            'Property "%s" does not exist on "%s"',
            $name, get_class($this)
        ));
    }

    /**
     * Return the localized name of this Item or
     * default to the name.
     *
     * @param string $locale Localization
     *
     * @return string
     */
    public function getLocalizedTitle($locale)
    {
        if (isset($this->title[$locale])) {
            return $this->title[$locale];
        }

        return ucfirst($this->name);
    }

    public function getParameter($name)
    {
        if (!isset($this->parameters[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown parameter "%s", known parameters: "%s"',
                $name, implode('", "', array_keys($this->parameters))
            ));
        }

        return $this->parameters[$name];
    }
}
