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

use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;

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

    function __construct(
        ContentMapperInterface $mapper
    )
    {
        $this->mapper = $mapper;
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

        // add default empty embedded property
        $result['_embedded'] = array();
        // add api links
        $result['_links'] = array(
            'self' => $this->apiBasePath . '/' . $structure->getUuid(),
            'children' => $this->apiBasePath . '?parent=' . $structure->getUuid() . '&depth=' . $depth
        );

        return $result;
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
    public function saveIndexNode($data, $templateKey, $portalKey, $languageCode, $userId)
    {
        $structure = $this->getMapper()->saveStartPage(
            $data,
            $templateKey,
            $portalKey,
            $languageCode,
            $userId
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
     * Returns the content of a smartcontent configuration
     * @param array $smartContentConfig The config of the smart content
     * @param string $languageCode The desired language code
     * @param string $webspaceKey The webspace key
     * @return mixed
     */
    public function getSmartContentNodes(array $smartContentConfig, $languageCode, $webspaceKey)
    {
        $sql2 = 'SELECT * FROM [sulu:content] as c WHERE c.[title] = \'asdf\'';



        return $this->getMapper()->loadBySql2($sql2, $languageCode, $webspaceKey);
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
    public function saveNode($data, $templateKey, $portalKey, $languageCode, $userId, $uuid = null, $parentUuid = null)
    {
        $node = $this->getMapper()->save(
            $data,
            $templateKey,
            $portalKey,
            $languageCode,
            $userId,
            true,
            $uuid,
            $parentUuid
        );

        return $this->prepareNode($node);
    }
}
