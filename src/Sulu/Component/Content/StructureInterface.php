<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content;

use DateTime;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * Structure for template
 */
interface StructureInterface extends \JsonSerializable
{
    const STATE_TEST = 1;
    const STATE_PUBLISHED = 2;

    /**
     * @param string $language
     */
    public function setLanguage($language);

    /**
     * returns language of node
     * @return string
     */
    public function getLanguage();

    /**
     * @param string $webspace
     */
    public function setWebspace($webspace);

    /**
     * returns webspace of node
     * @return string
     */
    public function getWebspace();

    /**
     * id of node
     * @return int
     */
    public function getUuid();

    /**
     * sets id of node
     * @param $uuid
     */
    public function setUuid($uuid);

    /**
     * returns id of creator
     * @return int
     */
    public function getCreator();

    /**
     * sets user id of creator
     * @param $userId int id of creator
     */
    public function setCreator($userId);

    /**
     * returns user id of changer
     * @return int
     */
    public function getChanger();

    /**
     * sets user id of changer
     * @param $userId int id of changer
     */
    public function setChanger($userId);

    /**
     * return created datetime
     * @return DateTime
     */
    public function getCreated();

    /**
     * sets created datetime
     * @param DateTime $created
     */
    public function setCreated(DateTime $created);

    /**
     * returns changed DateTime
     * @return DateTime
     */
    public function getChanged();

    /**
     * sets changed datetime
     * @param DateTime $changed
     */
    public function setChanged(DateTime $changed);

    /**
     * key of template definition
     * @return string
     */
    public function getKey();

    /**
     * twig template of template definition
     * @return string
     */
    public function getView();

    /**
     * controller which renders the template definition
     * @return string
     */
    public function getController();

    /**
     * cacheLifeTime of template definition
     * @return int
     */
    public function getCacheLifeTime();

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
     * @return array
     */
    public function getProperties();

    /**
     * @param boolean $hasChildren
     */
    public function setHasChildren($hasChildren);

    /**
     * returns true if node has children
     * @return boolean
     */
    public function getHasChildren();

    /**
     * @param StructureInterface[] $children
     */
    public function setChildren($children);

    /**
     * returns children array
     * @return StructureInterface[]
     */
    public function getChildren();

    /**
     * @param int $state
     * @return int
     */
    public function setNodeState($state);

    /**
     * returns state of node
     * @return int
     */
    public function getNodeState();

    /**
     * returns true if state of site is "published"
     * @return boolean
     */
    public function getPublished();

    /**
     * @param int $globalState
     */
    public function setGlobalState($globalState);

    /**
     * returns global state of node (with inheritance)
     * @return int
     */
    public function getGlobalState();

    /**
     * @param \DateTime $publishedDate
     */
    public function setPublishedDate($publishedDate);

    /**
     * returns first published date
     * @return \DateTime
     */
    public function getPublishedDate();

    /**
     * returns true if this node is shown in navigation
     * @return boolean
     */
    public function getNavigation();

    /**
     * @param boolean $showInNavigation
     */
    public function setNavigation($showInNavigation);

    /**
     * returns an array of property value pairs
     * @return array
     */
    public function toArray();
}
