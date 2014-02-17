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
     * The name of the root node of PHPCR
     * @var string
     */
    private $baseNodeName;

    /**
     * The name of the content nodes in PHPCR
     * @var string
     */
    private $contentNodeName;

    /**
     * for returning self link in get action
     * @var string
     */
    private $apiBasePath = '/admin/api/nodes';

    function __construct(ContentMapperInterface $mapper, $baseNodeName, $contentNodeName)
    {
        $this->mapper = $mapper;
        $this->baseNodeName = $baseNodeName;
        $this->contentNodeName = $contentNodeName;
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
     * Returns the content of a smart content configuration
     * @param array $smartContentConfig The config of the smart content
     * @param string $languageCode The desired language code
     * @param string $webspaceKey The webspace key
     * @return mixed
     */
    public function getSmartContentNodes(array $smartContentConfig, $languageCode, $webspaceKey)
    {
        // build sql2 query
        $sql2 = 'SELECT * FROM [sulu:content] AS c';
        $sql2Where = array();
        $sql2Order = array();

        // build where clause for datasource
        if (isset($smartContentConfig['dataSource']) && !empty($smartContentConfig['dataSource'])) {
            $sqlFunction =
                (isset($smartContentConfig['includeSubFolders']) && $smartContentConfig['includeSubFolders'])
                    ? 'ISDESCENDANTNODE' : 'ISCHILDNODE';
            $dataSource = '/' . $this->baseNodeName . '/' . $webspaceKey . '/' . $this->contentNodeName;
            $dataSource .= $smartContentConfig['dataSource'];
            $sql2Where[] = $sqlFunction . '(\'' . $dataSource . '\')';
        }

        // build where clause for tags
        if (!empty($smartContentConfig['tags'])) {
            foreach ($smartContentConfig['tags'] as $tag) {
                $sql2Where[] = 'c.[sulu_locale:' . $languageCode . '-tags] = ' . $tag;
            }
        }

        // build order clause
        if (isset($smartContentConfig['sortBy']) && is_string($smartContentConfig['sortBy'])) {
            // rewrite to array, if string as sort column is given
            $smartContentConfig['sortBy'] = array($smartContentConfig['sortBy']);
        }

        if (!empty($smartContentConfig['sortBy'])) {
            foreach ($smartContentConfig['sortBy'] as $sortColumn) {
                // TODO implement more generic
                $sql2Order[] = 'c.[sulu:' . $sortColumn . ']';
            }
        }

        // append where clause to sql2 query
        if (!empty($sql2Where)) {
            $sql2 .= ' WHERE ' . join(' AND ', $sql2Where);
        }

        // append order clause
        if (!empty($sql2Order)) {
            $sortOrder = (isset($smartContentConfig['sortMethod']) && $smartContentConfig['sortMethod'] == 'asc')
                ? 'ASC' : 'DESC';
            $sql2 .= ' ORDER BY ' . join(', ', $sql2Order) . ' ' . $sortOrder;
        }

        // set limit if given
        $limit = null;
        if (isset($smartContentConfig['limitResult'])) {
            $limit = $smartContentConfig['limitResult'];
        }

        // execute query and return results
        $nodes = $this->getMapper()->loadBySql2($sql2, $languageCode, $webspaceKey, $limit);

        return $nodes;
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
     * {@inheritdoc}
     */
    public function saveNode($data, $templateKey, $portalKey, $languageCode, $userId, $uuid = null, $parentUuid = null, $state = null)
    {
        $node = $this->getMapper()->save(
            $data,
            $templateKey,
            $portalKey,
            $languageCode,
            $userId,
            true,
            $uuid,
            $parentUuid,
            $state
        );

        return $this->prepareNode($node);
    }
}
