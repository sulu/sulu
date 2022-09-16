<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Compat;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template.
 */
interface StructureInterface extends \JsonSerializable
{
    public const STATE_TEST = 1;

    public const STATE_PUBLISHED = 2;

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
     * @return string
     */
    public function getUuid();

    /**
     * sets id of node.
     *
     * @param string $uuid
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
     * @param int $userId id of creator
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
     * @param int $userId id of changer
     */
    public function setChanger($userId);

    /**
     * return created datetime.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * sets created datetime.
     */
    public function setCreated(\DateTime $created);

    /**
     * returns changed DateTime.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * sets changed datetime.
     */
    public function setChanged(\DateTime $changed);

    /**
     * key of template definition.
     *
     * @return string
     */
    public function getKey();

    /**
     * returns a property instance with given name.
     *
     * @param string $name name of property
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
     * @param string $name string name of property
     */
    public function getPropertyValue($name);

    /**
     * returns all property names.
     *
     * @return array
     */
    public function getPropertyNames();

    /**
     * @param StructureType $type
     */
    public function setType($type);

    /**
     * Return type of structure.
     *
     * @return StructureType
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
     * @param bool $highest
     *
     * @return PropertyInterface
     */
    public function getPropertyByTagName($tagName, $highest = true);

    /**
     * returns properties with given tag name sorted by priority.
     *
     * @param string $tagName
     *
     * @return PropertyInterface[]
     *
     * @throws NoSuchPropertyException
     */
    public function getPropertiesByTagName($tagName);

    /**
     * returns value of property with given tag name.
     *
     * @param string $tagName
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
     */
    public function copyFrom(self $structure);
}
