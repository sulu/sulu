<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use DateTime;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template.
 */
interface StructureInterface extends \JsonSerializable
{
    const STATE_TEST = 1;
    const STATE_PUBLISHED = 2;

    /**
     * @param string $language
     */
    public function setLanguageCode($language);

    /**
     * returns language of node.
     *
     * @return string
     */
    public function getLanguageCode();

    /**
     * @param string $webspace
     */
    public function setWebspaceKey($webspace);

    /**
     * returns webspace of node.
     *
     * @return string
     */
    public function getWebspaceKey();

    /**
     * id of node.
     *
     * @return int
     */
    public function getUuid();

    /**
     * sets id of node.
     *
     * @param $uuid
     */
    public function setUuid($uuid);

    /**
     * gets user id of creator.
     *
     * @return int
     */
    public function getCreator();

    /**
     * sets user id of creator.
     *
     * @param $userId int id of creator
     */
    public function setCreator($userId);

    /**
     * returns user id of changer.
     *
     * @return int
     */
    public function getChanger();

    /**
     * sets user id of changer.
     *
     * @param $userId int id of changer
     */
    public function setChanger($userId);

    /**
     * return created datetime.
     *
     * @return DateTime
     */
    public function getCreated();

    /**
     * sets created datetime.
     *
     * @param DateTime $created
     */
    public function setCreated(DateTime $created);

    /**
     * returns changed DateTime.
     *
     * @return DateTime
     */
    public function getChanged();

    /**
     * sets changed datetime.
     *
     * @param DateTime $changed
     */
    public function setChanged(DateTime $changed);

    /**
     * key of template definition.
     *
     * @return string
     */
    public function getKey();

    /**
     * returns a property instance with given name.
     *
     * @param $name string name of property
     *
     * @return PropertyInterface
     *
     * @throws NoSuchPropertyException
     */
    public function getProperty($name);

    /**
     * checks if a property exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty($name);

    /**
     * returns an array of properties.
     *
     * @param bool $flatten
     *
     * @return PropertyInterface[]
     */
    public function getProperties($flatten = false);

    /**
     * @param bool $hasChildren
     */
    public function setHasChildren($hasChildren);

    /**
     * returns true if node has children.
     *
     * @return bool
     */
    public function getHasChildren();

    /**
     * @param StructureInterface[] $children
     */
    public function setChildren($children);

    /**
     * returns children array.
     *
     * @return StructureInterface[]
     */
    public function getChildren();

    /**
     * returns true if state of site is "published".
     *
     * @return bool
     */
    public function getPublishedState();

    /**
     * @param \DateTime $published
     */
    public function setPublished($published);

    /**
     * returns first published date.
     *
     * @return \DateTime
     */
    public function getPublished();

    /**
     * return value of property with given name.
     *
     * @param $name string name of property
     *
     * @return mixed
     */
    public function getPropertyValue($name);

    /**
     * returns all property names.
     *
     * @return array
     */
    public function getPropertyNames();

    /**
     * @param \Sulu\Component\Content\Compat\StructureType $type
     */
    public function setType($type);

    /**
     * Return type of structure.
     *
     * @return \Sulu\Component\Content\Compat\StructureType
     */
    public function getType();

    /**
     * return the node path.
     *
     * @return string
     */
    public function getPath();

    /**
     * @param string $path
     */
    public function setPath($path);

    /**
     * @param bool $hasTranslation
     */
    public function setHasTranslation($hasTranslation);

    /**
     * return true if structure translation is valid.
     *
     * @return bool
     */
    public function getHasTranslation();

    /**
     * returns an array of property value pairs.
     *
     * @param bool $complete True if result should be representation of full node
     *
     * @return array
     *
     * @deprecated Use the serializer instead
     */
    public function toArray($complete = true);

    /**
     * returns a property instance with given tag name.
     *
     * @param string $tagName
     * @param $highest
     *
     * @return PropertyInterface
     */
    public function getPropertyByTagName($tagName, $highest = true);

    /**
     * returns properties with given tag name sorted by priority.
     *
     * @param string $tagName
     *
     * @throws \Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     *
     * @return PropertyInterface[]
     */
    public function getPropertiesByTagName($tagName);

    /**
     * returns value of property with given tag name.
     *
     * @param string $tagName
     *
     * @return mixed
     */
    public function getPropertyValueByTagName($tagName);

    /**
     * indicates tag exists.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag($tag);

    /**
     * @return int
     */
    public function getNodeType();

    /**
     * returns node name addicted to the type.
     *
     * @return string
     */
    public function getNodeName();

    /**
     * returns title of property.
     *
     * @param string $languageCode
     *
     * @return string
     */
    public function getLocalizedTitle($languageCode);

    /**
     * return the published status of the node.
     *
     * @return int
     */
    public function getNodeState();

    /**
     * Copy static values from another structure.
     *
     * @param StructureInterface $structure
     */
    public function copyFrom(StructureInterface $structure);
}
