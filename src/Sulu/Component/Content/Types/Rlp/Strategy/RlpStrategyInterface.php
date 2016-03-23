<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;

/**
 * InterfaceDefinition of Resource Locator Path Strategy.
 */
interface RlpStrategyInterface
{
    /**
     * returns name of RLP Strategy (e.g. whole-tree).
     *
     * @return string
     */
    public function getName();

    /**
     * returns whole path for given ContentNode.
     *
     * @param string $title title of new node
     * @param string $parentPath parent path of new contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string whole path
     */
    public function generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns whole path for given ContentNode.
     *
     * @param string $title title of new node
     * @param string $uuid uuid for node to generate rl
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string whole path
     */
    public function generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * creates a new route for given path.
     *
     * @param ResourceSegmentBehavior $document
     * @param int $userId
     *
     * @return
     */
    public function save(ResourceSegmentBehavior $document, $userId);

    /**
     * returns path for given contentNode.
     *
     * @param ResourceSegmentBehavior $document reference node
     *
     * @return string path
     */
    public function loadByContent(ResourceSegmentBehavior $document);

    /**
     * returns path for given contentNode.
     *
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string path
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns history for given contentNode.
     *
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return ResourceLocatorInformation[]
     */
    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * returns the uuid of referenced content node.
     *
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * checks if path is valid.
     *
     * @param string $path path of route
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return bool
     */
    public function isValid($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * deletes given resource locator node.
     *
     * @param string $path of resource locator node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null);

    /**
     * restore given resource locator.
     *
     * @param string $path of resource locator
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null);
}
