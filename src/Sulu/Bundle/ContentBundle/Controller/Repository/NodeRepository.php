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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;

class NodeRepository implements NodeRepositoryInterface
{

    /**
     * @var ContentMapperInterface
     */
    private $mapper;
    /**
     * @var GetContactInterface
     */
    private $contact;
    /**
     * for returning self link in get action
     * @var string
     */
    private $apiBasePath = '/admin/api/nodes';

    function __construct(ContentMapperInterface $mapper, GetContactInterface $contact)
    {
        $this->mapper = $mapper;
        $this->contact = $contact;
    }

    /**
     * returns node for given uuid
     * @param $uuid
     * @param $portalKey
     * @param $languageCode
     * @return array
     */
    public function getNode($uuid, $portalKey, $languageCode)
    {
        $structure = $this->getMapper()->load($uuid, $portalKey, $languageCode);

        return $this->prepareNode($structure);
    }

    /**
     * removes given node
     * @param $uuid
     * @param $portalKey
     */
    public function deleteNode($uuid, $portalKey)
    {
        $this->getMapper()->delete($uuid, $portalKey);
    }

    /**
     * return content mapper
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->mapper;
    }

    /**
     * returns finished Node (with _links and _embedded)
     * @param StructureInterface $structure
     * @return array
     */
    protected function prepareNode(StructureInterface $structure)
    {
        $result = $structure->toArray();

        // replace creator, changer
        $result['creator'] = $this->getContact($structure->getCreator());
        $result['changer'] = $this->getContact($structure->getChanger());

        // add default empty embedded property
        $result['_embedded'] = array();
        // add api links
        $result['_links'] = array(
            'self' => $this->apiBasePath . '/' . $structure->getUuid(),
            'children' => $this->apiBasePath . '?parent=' . $structure->getUuid() . '&depth=1'
        );

        return $result;
    }

    protected function getContact($id)
    {
        return $this->contact->getContact($id);
    }

    /**
     * @param string $apiBasePath
     */
    public function setApiBasePath($apiBasePath)
    {
        $this->apiBasePath = $apiBasePath;
    }
}
