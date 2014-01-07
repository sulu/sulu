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
use Jackalope\NotImplementedException;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

class NodeRepository implements NodeRepositoryInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * for returning self link in get action
     * @var string
     */
    private $apiBasePath = '/admin/api/nodes';

    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var Registry
     */
    private $doctrine;

    function __construct(
        ContentMapperInterface $mapper,
        Registry $doctrine,
        SecurityContextInterface $securityContext
    )
    {
        $this->mapper = $mapper;
        $this->doctrine = $doctrine;
        $this->securityContext = $securityContext;
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
     * @param int $depth
     * @return array
     */
    protected function prepareNode(StructureInterface $structure, $depth = 1)
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
            'children' => $this->apiBasePath . '?parent=' . $structure->getUuid() . '&depth=' . $depth
        );

        return $result;
    }

    protected function getContact($id)
    {
        /** @var User $user */
        $user = $this->doctrine->getRepository('SuluSecurityBundle:User')->find($id);
        return $user->getFullName();
    }

    protected function getUserId()
    {
        return $this->securityContext->getToken()->getUser()->getId();
    }

    /**
     * @param string $apiBasePath
     */
    public function setApiBasePath($apiBasePath)
    {
        $this->apiBasePath = $apiBasePath;
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
     * returns start node for given portal
     * @param string $portalKey
     * @param string $languageCode
     * @return array
     */
    public function getIndexNode($portalKey, $languageCode)
    {
        $structure = $this->getMapper()->loadStartPage($portalKey, $languageCode);

        return $this->prepareNode($structure);
    }

    /**
     * save start page of given portal
     * @param array $data
     * @param string $templateKey
     * @param string $portalKey
     * @param string $languageCode
     * @return array
     */
    public function saveIndexNode($data, $templateKey, $portalKey, $languageCode)
    {
        $structure = $this->getMapper()->saveStartPage(
            $data,
            $templateKey,
            $portalKey,
            $languageCode,
            $this->getUserId()
        );

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
     * returns a list of nodes
     * @param string $parent uuid of parent node
     * @param string $portalKey key of current portal
     * @param string $languageCode
     * @param int $depth
     * @param bool $flat
     * @return array
     */
    public function getNodes($parent, $portalKey, $languageCode, $depth = 1, $flat = true)
    {
        $nodes = $this->getMapper()->loadByParent($parent, $portalKey, $languageCode, $depth, $flat);

        if ($parent != null) {
            $parentNode = $this->getMapper()->load($parent, $portalKey, $languageCode);
        } else {
            $parentNode = $this->getMapper()->loadStartPage($portalKey, $languageCode);
        }
        $result = $this->prepareNodesTree($parentNode);
        $result['_embedded'] = $this->prepareNodesTree($nodes);
        $result['total'] = sizeof($result['_embedded']);

        return $result;
    }

    /**
     * @param StructureInterface[] $nodes
     * @return array
     */
    private function prepareNodesTree($nodes)
    {
        $results = array();
        foreach ($nodes as $node) {
            $result = $this->prepareNode($node);
            if ($node->getHasChildren() && $node->getChildren() != null) {
                $result['_embedded'] = $this->prepareNodesTree($node->getChildren());
            }
            $results[] = $result;
        }

        return $results;
    }

    /**
     * save node with given uuid or creates a new one
     * @param array $data
     * @param string $templateKey
     * @param string $portalKey
     * @param string $languageCode
     * @param string $uuid
     * @param string $parentUuid
     * @return array
     */
    public function saveNode($data, $templateKey, $portalKey, $languageCode, $uuid = null, $parentUuid = null)
    {
        $node = $this->getMapper()->save(
            $data,
            $templateKey,
            $portalKey,
            $languageCode,
            $this->getUserId(),
            true,
            $uuid,
            $parentUuid
        );

        return $this->prepareNode($node);
    }
}
