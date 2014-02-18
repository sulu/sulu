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
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class NodeRepository implements NodeRepositoryInterface
{
    /**
     * @var ContentMapperInterface
     */
    private $mapper;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * for returning self link in get action
     * @var string
     */
    private $apiBasePath = '/admin/api/nodes';

    function __construct(ContentMapperInterface $mapper, SessionManagerInterface $sessionManager)
    {
        $this->mapper = $mapper;
        $this->sessionManager = $sessionManager;
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
        if ($structure->getCreator() !== 0) {
            $result['creator'] = $this->getFullNameByUserId($structure->getCreator());
        } else {
            $result['creator'] = '';
        }
        if ($structure->getChanger() !== 0) {
            $result['changer'] = $this->getFullNameByUserId($structure->getChanger());
        } else {
            $result['changer'] = '';
        }

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
     * @param integer $userId
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
     * @param array $filterConfig The config of the smart content
     * @param string $languageCode The desired language code
     * @param string $webspaceKey The webspace key
     * @param boolean $preview If true also  unpublished pages will be returned
     * @return mixed
     */
    public function getFilteredNodes(array $filterConfig, $languageCode, $webspaceKey, $preview = false)
    {
        // build sql2 query
        $sql2 = 'SELECT * FROM [sulu:content] AS c';
        $sql2Where = array();
        $sql2Order = array();

        // build where clause for datasource
        if (isset($filterConfig['dataSource']) && !empty($filterConfig['dataSource'])) {
            $sqlFunction =
                (isset($filterConfig['includeSubFolders']) && $filterConfig['includeSubFolders'])
                    ? 'ISDESCENDANTNODE' : 'ISCHILDNODE';
            $node = $this->sessionManager->getContentNode($webspaceKey);
            $dataSource = $node->getPath();
            $dataSource .= $filterConfig['dataSource'];
            $sql2Where[] = $sqlFunction . '(\'' . $dataSource . '\')';
        }

        // build where clause for tags
        if (!empty($filterConfig['tags'])) {
            foreach ($filterConfig['tags'] as $tag) {
                $sql2Where[] = 'c.[sulu_locale:' . $languageCode . '-tags] = ' . $tag;
            }
        }

        // search only for published pages
        if (!$preview) {
            $sql2Where[] = 'c.[sulu_locale:' . $languageCode . '-sulu-state] = ' . StructureInterface::STATE_PUBLISHED;
        }

        // build order clause
        if (!empty($filterConfig['sortBy'])) {
            foreach ($filterConfig['sortBy'] as $sortColumn) {
                // TODO implement more generic
                $sql2Order[] = 'c.[sulu_locale:' . $languageCode . '-sulu-' . $sortColumn . ']';
            }
        }

        // append where clause to sql2 query
        if (!empty($sql2Where)) {
            $sql2 .= ' WHERE ' . join(' AND ', $sql2Where);
        }

        // append order clause
        if (!empty($sql2Order)) {
            $sortOrder = (isset($filterConfig['sortMethod']) && $filterConfig['sortMethod'] == 'asc')
                ? 'ASC' : 'DESC';
            $sql2 .= ' ORDER BY ' . join(', ', $sql2Order) . ' ' . $sortOrder;
        }

        // set limit if given
        $limit = null;
        if (isset($filterConfig['limitResult'])) {
            $limit = $filterConfig['limitResult'];
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
    )
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
            $state,
            $showInNavigation
        );

        return $this->prepareNode($node);
    }
}
