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

use Sulu\Component\Content\Structure\Item;

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
     * Magic setter to catch bad property calls
     */
    public function __set($field, $value)
    {
        throw new \InvalidArgumentException(sprintf(
            'Property "%s" does not exist on "%s"',
            $field, get_class($this)
        ));
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

    /**
     * TODO: Rename to getParameters
     *
     * {@inheritDoc}
     */
    public function getParams() 
    {
        return $this->parameters;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getTags() 
    {
        return $this->tags;
    }

    /**
     * {@inheritDoc}
     */
    public function getTag($tagName)
    {
        foreach ($this->tags as $tag) {
            if ($tag['name'] === $tagName) {
                return $tag;
            }
        }
    }

    /**
     * TODO: This is duplicated
     * @deprecated
     *
     * {@inheritDoc}
     */
    public function getTitle($locale) 
    {
        return $this->getLocalizedTitle($locale);
    }

    /**
     * Return a description for this item
     *
     * @return string
     */
    public function getInfoText($locale) 
    {
        return $this->description[$locale];
    }
}
