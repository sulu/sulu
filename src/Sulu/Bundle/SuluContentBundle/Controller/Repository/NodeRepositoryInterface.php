<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Controller\Repository;


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
     * returns start node for given portal
     * @param string $portalKey
     * @param string $languageCode
     * @return array
     */
    public function getStartNode($portalKey, $languageCode);

    /**
     * save start page of given portal
     * @param array $data
     * @param string $templateKey
     * @param string $portalKey
     * @param string $languageCode
     * @return array
     */
    public function saveStartNode($data, $templateKey, $portalKey, $languageCode);
    
    /**
     * removes given node
     * @param $uuid
     * @param $portalKey
     */
    public function deleteNode($uuid, $portalKey);
}
