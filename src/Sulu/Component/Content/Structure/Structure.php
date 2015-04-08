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

use DTL\Component\Content\Structure\Property;

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
     * Return all direct child properties of this structure, ignoring
     * Sections
     *
     * @return Property[]
     */
    public function getProperties()
    {
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
     * Returns a model representation of this structure - the structure
     * without presentation elements: without Sections.
     *
     * @return Structure
     */
    public function transformToModel()
    {
        $structure = clone $this;
        $structure->children = $this->getProperties();

        return $structure;
    }

    /**
     * Return all the localized properties
     *
     * @return Property[]
     */
    public function getLocalizedProperties()
    {
        return array_filter($this->getProperties(), function (Property $property) {
            return $property->localized === true;
        });
    }

    /**
     * Return all the non-localized properties
     *
     * @return Property[]
     */
    public function getNonLocalizedProperties()
    {
        return array_filter($this->getProperties(), function (Property $property) {
            return $property->localized === false;
        });
    }

    public function getPropertiesByTag($tagName)
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
}
