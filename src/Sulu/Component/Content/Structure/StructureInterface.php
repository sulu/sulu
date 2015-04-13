<?php

namespace Sulu\Component\Content\Structure;

interface StructureInterface
{
    /**
     * returns a property instance with given name
     * @param $name string name of property
     * @return PropertyInterface
     * @throws NoSuchPropertyException
     */
    public function getProperty($name);

    /**
     * checks if a property exists
     * @param string $name
     * @return boolean
     */
    public function hasProperty($name);

    /**
     * returns an array of properties
     * @param bool $flatten
     * @return PropertyInterface[]
     */
    public function getProperties($flatten = false);

    /**
     * returns all property names
     * @return array
     */
    public function getPropertyNames();

    /**
     * returns a property instance with given tag name
     * @param string $tagName
     * @param $highest
     * @return PropertyInterface
     */
    public function getPropertyByTagName($tagName, $highest = true);

    /**
     * returns properties with given tag name sorted by priority
     * @param string $tagName
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     * @return PropertyInterface[]
     */
    public function getPropertiesByTagName($tagName);

    /**
     * indicates tag exists
     * @param string $tag
     * @return bool
     */
    public function hasTag($tag);

    /**
     * returns title of property
     * @param string $languageCode
     * @return string
     */
    public function getLocalizedTitle($languageCode);
}
