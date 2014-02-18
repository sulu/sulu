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


interface NodeRepositoryInterface {

    /**
     * returns node for given uuid
     * @param $uuid
     * @param $portalKey
     * @param $languageCode
     * @return array
     */
    public function getNode($uuid, $portalKey, $languageCode);

    /**
     * returns a list of nodes
     * @param string $parent uuid of parent node
     * @param string $portalKey key of current portal
     * @param string $languageCode
     * @param int $depth
     * @param bool $flat
     * @return array
     */
    public function getNodes($parent, $portalKey, $languageCode, $depth = 1, $flat = true);

    /**
     * Returns the content of a smartcontent configuration
     * @param array $filterConfig The config of the smart content
     * @param string $languageCode The desired language code
     * @param string $webspaceKey The webspace key
     * @return mixed
     */
    public function getFilteredNodes(array $filterConfig, $languageCode, $webspaceKey);

    /**
     * returns start node for given portal
     * @param string $portalKey
     * @param string $languageCode
     * @return array
     */
    public function getIndexNode($portalKey, $languageCode);

    /**
     * save node with given uuid or creates a new one
     * @param array $data
     * @param string $templateKey
     * @param string $portalKey
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
        $portalKey,
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
     * @param string $portalKey
     * @param string $languageCode
     * @param $userId
     * @return array
     */
    public function saveIndexNode($data, $templateKey, $portalKey, $languageCode, $userId);
    
    /**
     * removes given node
     * @param $uuid
     * @param $portalKey
     */
    public function deleteNode($uuid, $portalKey);
}
