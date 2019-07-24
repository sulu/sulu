<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Metadata;

class PropertiesMetadata extends ItemMetadata
{
    /**
     * The resource from which this structure was loaded
     * (useful for debugging).
     *
     * @var string
     */
    protected $resource;

    /**
     * @var PropertyMetadata[]
     */
    protected $properties;

    /**
     * Return the resource from which this structure was loaded.
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * Sets the resource from which this structure was loaded.
     */
    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Return a model property.
     *
     * @see StructureMetadata::getProperties()
     *
     * @param string $name
     *
     * @return PropertyMetadata
     */
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unknown model property "%s", in structure "%s". Known model properties: "%s". Loaded from "%s"',
                    $name,
                    $this->getName(),
                    implode('", "', array_keys($this->properties)),
                    $this->resource
                )
            );
        }

        return $this->properties[$name];
    }

    /**
     * Return all model properties.
     *
     * The "model" set of properties does not include UI elements
     * such as sections.
     *
     * @return PropertyMetadata[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Populate the $properties property with only those properties which are not related to the UI (i.e. the sections).
     *
     * The data is therefore duplicated, but this does not matter as we only create this data once.
     *
     * This should be called once after creating the structure and (therefore before writing to the cache).
     */
    public function burnProperties()
    {
        $this->properties = $this->removeSectionProperties($this->children);
    }

    /**
     * @param ItemMetadata[] $children
     */
    private function removeSectionProperties(array $children)
    {
        $properties = [];
        foreach ($children as $child) {
            if ($child instanceof SectionMetadata) {
                $properties = array_merge($properties, $this->removeSectionProperties($child->getChildren()));

                continue;
            }

            $properties[$child->getName()] = $child;
        }

        return $properties;
    }

    /**
     * Return true if a property with the given name exists.
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, $this->properties);
    }

    /**
     * Return true if the structure contains a property with the given
     * tag name.
     *
     * @param string $tagName
     *
     * @return PropertyMetadata
     */
    public function getPropertyByTagName($tagName, $highest = true)
    {
        $properties = $this->getPropertiesByTagName($tagName);

        if (!$properties) {
            throw new \InvalidArgumentException(
                sprintf(
                    'No property with tag "%s" exists. In structure "%s" loaded from "%s"',
                    $tagName,
                    $this->name,
                    $this->resource
                )
            );
        }

        return reset($properties);
    }

    /**
     * Return true if the structure contains a property with the given
     * tag name.
     *
     * @param string $tagName
     *
     * @return bool
     */
    public function hasPropertyWithTagName($tagName)
    {
        return (bool) count($this->getPropertiesByTagName($tagName));
    }

    /**
     * Return all properties with the given tag name.
     *
     * @param string $tagName
     *
     * @return PropertyMetadata[]
     */
    public function getPropertiesByTagName($tagName)
    {
        $properties = [];

        foreach ($this->properties as $property) {
            foreach ($property->tags as $tag) {
                if ($tag['name'] == $tagName) {
                    $properties[$property->name] = $property;
                }
            }
        }

        return $properties;
    }
}
