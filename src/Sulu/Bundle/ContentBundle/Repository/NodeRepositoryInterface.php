<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Repository;

/**
 * repository for node objects
 */
interface NodeRepositoryInterface
{
    /**
     * returns node for given uuid
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param bool $breadcrumb
     * @param bool $complete
     * @param bool $excludeGhosts
     * @return array
     */
    public function getNode($uuid, $webspaceKey, $languageCode, $breadcrumb = false, $complete = true, $excludeGhosts = false);

    /**
     * returns a list of nodes
     * @param string $parent uuid of parent node
     * @param string $webspaceKey key of current portal
     * @param string $languageCode
     * @param int $depth
     * @param bool $flat
     * @param bool $complete
     * @param bool $excludeGhosts
     * @return array
     */
    public function getNodes($parent, $webspaceKey, $languageCode, $depth = 1, $flat = true, $complete = true, $excludeGhosts = false);

    /**
     * returns list of nodes with given ids
     * @param array $ids
     * @param string $webspaceKey
     * @param string $languageCode
     * @return array
     */
    public function getNodesByIds($ids, $webspaceKey, $languageCode);

    /**
     * returns webspace as node
     * @param $webspaceKey
     * @param $languageCode
     * @param int $depth
     * @param bool $excludeGhosts
     * @return mixed
     */
    public function getWebspaceNode(
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $excludeGhosts = false
    );

    /**
     * Returns the content of a smart content configuration
     * @param array $filterConfig The config of the smart content
     * @param string $languageCode The desired language code
     * @param string $webspaceKey The webspace key
     * @param boolean $preview If true also  unpublished pages will be returned
     * @param bool $api If true result will be formated for HAL API
     * @return array
     */
    public function getFilteredNodes(array $filterConfig, $languageCode, $webspaceKey, $preview = false, $api = false);

    /**
     * returns start node for given portal
     * @param string $webspaceKey
     * @param string $languageCode
     * @return array
     */
    public function getIndexNode($webspaceKey, $languageCode);

    /**
     * save node with given uuid or creates a new one
     * @param array $data
     * @param string $templateKey
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
     * @param string $uuid
     * @param null $state
     * @param string $parentUuid
     * @param boolean $showInNavigation
     * @return array
     */
    public function saveNode(
        $data,
        $templateKey,
        $webspaceKey,
        $languageCode,
        $userId,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $showInNavigation = null
    );

    /**
     * save start page of given portal
     * @param array $data
     * @param string $templateKey
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
     * @return array
     */
    public function saveIndexNode($data, $templateKey, $webspaceKey, $languageCode, $userId);

    /**
     * removes given node
     * @param string $uuid
     * @param string $webspaceKey
     */
    public function deleteNode($uuid, $webspaceKey);

    /**
     * returns tree to content node given by uuid
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param boolean $excludeGhosts
     * @param bool $appendWebspaceNode if TRUE webspace will added as own node in first layer
     * @return array
     */
    public function getNodesTree(
        $uuid,
        $webspaceKey,
        $languageCode,
        $excludeGhosts = false,
        $appendWebspaceNode = false
    );

    /**
     * returns data of given extension api ready
     * @param string $uuid
     * @param string $extension
     * @param string $webspaceKey
     * @param string $languageCode
     * @return array
     */
    public function loadExtensionData($uuid, $extension, $webspaceKey, $languageCode);

    /**
     * save extension data
     * @param string $uuid
     * @param array $data
     * @param string $extensionName
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
     * @return array
     */
    public function saveExtensionData($uuid, $data, $extensionName, $webspaceKey, $languageCode, $userId);

    /**
     * move node and returns new data
     * @param string $uuid
     * @param string $destinationUuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $userId
     * @return array
     */
    public function moveNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId);

    /**
     * copy node and returns new data
     * @param string $uuid
     * @param string $destinationUuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $userId
     * @return array
     */
    public function copyNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId);

    /**
     * order given node before another
     * @param string $uuid
     * @param string $beforeUuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param integer $userId
     * @return array
     */
    public function orderBefore($uuid, $beforeUuid, $webspaceKey, $languageCode, $userId);
}
