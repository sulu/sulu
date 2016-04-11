<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

/**
 * Base class for all structure related metadata classes.
 */
abstract class ItemMetadata
{
    /**
     * Name of this item.
     *
     * @var string
     */
    public $name;

    /**
     * The title of this property|structure e.g. [["de": "Artikles", "en": "Articles"]].
     *
     * @var array
     */
    public $title = [];

    /**
     * Description of this property|structure e.g. [["de": "Liste von Artikeln", "en": "List of articles"]].
     *
     * @var array
     */
    public $description = [];

    /**
     * Tags, e.g.
     *
     * ````
     * array(
     *     array('name' => 'sulu_search.field', 'type' => 'string')
     * )
     * ````
     *
     * @var array
     */
    public $tags = [];

    /**
     * Parameters applying to the property.
     *
     * ````
     * array(
     *     'placeholder' => 'Enter some text',
     * )
     * ````
     *
     * @var array
     */
    public $parameters = [];

    /**
     * Children of this item, f.e. properties, sections or structures.
     *
     * @var Item[]
     */
    public $children = [];

    /**
     * @param mixed $name
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Magic setter to catch bad property calls.
     */
    public function __set($field, $value)
    {
        throw new \InvalidArgumentException(sprintf(
            'Property "%s" does not exist on "%s"',
            $field, get_class($this)
        ));
    }

    /**
     * Return the named property.
     *
     * @param string $name
     *
     * @return ItemMetadata
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
     *
     * @return bool
     */
    public function hasChild($name)
    {
        return isset($this->children[$name]);
    }

    /**
     * Adds a child item.
     *
     * @param ItemMetadata $child
     */
    public function addChild(ItemMetadata $child)
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
     * Return the children of this item.
     *
     * @return ItemMetadata[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Return the localized name of this ItemMetadata or
     * default to the name.
     *
     * @param string $locale Localization
     *
     * @return string
     */
    public function getTitle($locale)
    {
        if (isset($this->title[$locale])) {
            return $this->title[$locale];
        }

        return ucfirst($this->name);
    }

    /**
     * Return the paramter with the given name.
     *
     * @param string $name
     *
     * @return mixed
     */
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
     * Return the name of this item.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the tags of this item.
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Return the named tag.
     *
     * @param string $tagName
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function getTag($tagName)
    {
        foreach ($this->tags as $tag) {
            if ($tag['name'] === $tagName) {
                return $tag;
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Unknown tag "%s"', $tagName
        ));
    }

    /**
     * Return true if this item has the named tag.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTag($name)
    {
        foreach ($this->tags as $tag) {
            if ($tag['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the parameters for this property.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Return the description of this property.
     *
     * @param string $locale
     *
     * @return string
     */
    public function getDescription($locale)
    {
        if (isset($this->description[$locale])) {
            return $this->description[$locale];
        }

        return '';
    }
}
