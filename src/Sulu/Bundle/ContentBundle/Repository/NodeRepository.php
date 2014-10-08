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

use Doctrine\ODM\PHPCR\PHPCRException;
use PHPCR\ItemNotFoundException;
use PHPCR\RepositoryException;
use Psr\Log\LoggerInterface;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

/**
 * repository for node objects
 */
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

    /**
     * @var UserManagerInterface
     */
    private $userManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    function __construct(
        ContentMapperInterface $mapper,
        SessionManagerInterface $sessionManager,
        UserManagerInterface $userManager,
        WebspaceManagerInterface $webspaceManager,
        LoggerInterface $logger
    ) {
        $this->mapper = $mapper;
        $this->sessionManager = $sessionManager;
        $this->userManager = $userManager;
        $this->webspaceManager = $webspaceManager;
        $this->logger = $logger;
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
     * returns user fullName
     * @param integer $id userId
     * @return string
     */
    protected function getFullNameByUserId($id)
    {
        return $this->userManager->getFullNameByUserId($id);
    }

    /**
     * returns finished Node (with _links and _embedded)
     * @param StructureInterface $structure
     * @param string $webspaceKey
     * @param string $languageCode
     * @param int $depth
     * @param bool $complete
     * @param bool $excludeGhosts
     * @param string|null $extension
     * @return array
     */
    protected function prepareNode(
        StructureInterface $structure,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $complete = true,
        $excludeGhosts = false,
        $extension = null
    ) {
        $result = $structure->toArray($complete);

        // add node name
        $result['sulu.node.name'] = $structure->getPropertyValueByTagName('sulu.node.name');

        // add default embedded property with empty nodes array
        $result['_embedded'] = array();
        $result['_embedded']['nodes'] = array();

        // add api links
        $result['_links'] = array(
            'self' => $this->apiBasePath . '/' . $structure->getUuid() . ($extension !== null ? '/' . $extension : ''),
            'children' => $this->apiBasePath . '?parent=' . $structure->getUuid()
                . '&depth=' . $depth . '&webspace=' . $webspaceKey . '&language=' . $languageCode . ($excludeGhosts === true ? '&exclude-ghosts=true' : '')
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
     * {@inheritdoc}
     */
    public function getNode(
        $uuid,
        $webspaceKey,
        $languageCode,
        $breadcrumb = false,
        $complete = true,
        $loadGhostContent = false
    ) {
        $structure = $this->getMapper()->load($uuid, $webspaceKey, $languageCode, $loadGhostContent);

        $result = $this->prepareNode($structure, $webspaceKey, $languageCode, 1, $complete);
        if ($breadcrumb) {
            $breadcrumb = $this->getMapper()->loadBreadcrumb($uuid, $languageCode, $webspaceKey);
            $result['breadcrumb'] = array();
            foreach ($breadcrumb as $item) {
                $result['breadcrumb'][$item->getDepth()] = $item->toArray();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexNode($webspaceKey, $languageCode)
    {
        $structure = $this->getMapper()->loadStartPage($webspaceKey, $languageCode);

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function saveIndexNode($data, $templateKey, $webspaceKey, $languageCode, $userId)
    {
        $structure = $this->getMapper()->saveStartPage(
            $data,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId
        );

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteNode($uuid, $webspaceKey)
    {
        $this->getMapper()->delete($uuid, $webspaceKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getNodes(
        $parent,
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $flat = true,
        $complete = true,
        $excludeGhosts = false
    ) {
        $nodes = $this->getMapper()->loadByParent(
            $parent,
            $webspaceKey,
            $languageCode,
            $depth,
            $flat,
            false,
            $excludeGhosts
        );

        $parentNode = $this->getParentNode($parent, $webspaceKey, $languageCode);
        $result = $this->prepareNode($parentNode, $webspaceKey, $languageCode, 1, $complete, $excludeGhosts);
        $result['_embedded']['nodes'] = $this->prepareNodesTree(
            $nodes,
            $webspaceKey,
            $languageCode,
            $complete,
            $excludeGhosts
        );
        $result['total'] = sizeof($result['_embedded']['nodes']);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesByIds(
        $ids,
        $webspaceKey,
        $languageCode
    ) {
        $result = array();
        $idString = '';

        if (!empty($ids)) {
            foreach ($ids as $id) {
                try {
                    if (!empty($id)) {
                        $result[] = $this->getNode($id, $webspaceKey, $languageCode);
                    }
                } catch (ItemNotFoundException $ex) {
                    $this->logger->warning(
                        sprintf("%s in internal links not found. Exception: %s", $id, $ex->getMessage())
                    );
                }
            }
            $idString = implode(',', $ids);
        }

        return array(
            '_embedded' => array(
                'nodes' => $result
            ),
            'total' => sizeof($result),
            '_links' => array(
                'self' => $this->apiBasePath . '?ids=' . $idString
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWebspaceNode(
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $excludeGhosts = false
    ) {
        $webspace = $this->webspaceManager->getWebspaceCollection()->getWebspace($webspaceKey);

        if ($depth > 0) {
            $nodes = $this->getMapper()->loadByParent(
                null,
                $webspaceKey,
                $languageCode,
                $depth,
                false,
                false,
                $excludeGhosts
            );
            $embedded = $this->prepareNodesTree($nodes, $webspaceKey, $languageCode, true, $excludeGhosts);
        } else {
            $embedded = array();
        }

        $node = array(
            'id' => $this->sessionManager->getContentNode($webspace->getKey())->getIdentifier(),
            'path' => '/',
            'title' => $webspace->getName(),
            'hasSub' => true,
            '_embedded' => $embedded,
            '_links' => array(
                'children' => $this->apiBasePath . '?depth=' . $depth . '&webspace=' . $webspaceKey . '&language=' . $languageCode . ($excludeGhosts === true ? '&exclude-ghosts=true' : '')
            )
        );

        // init result
        $data = array();

        // add default empty embedded property
        $data['_embedded'] = array(
            'nodes' => array($node)
        );
        // add api links
        $data['_links'] = array(
            'self' => $this->apiBasePath . '/entry?depth=' . $depth . '&webspace=' . $webspaceKey . '&language=' . $languageCode . ($excludeGhosts === true ? '&exclude-ghosts=true' : ''),
        );

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilteredNodes(array $filterConfig, $languageCode, $webspaceKey, $preview = false, $api = false)
    {
        // build sql2 query
        $queryBuilder = new FilterNodesQueryBuilder($filterConfig, $this->sessionManager, $this->webspaceManager);
        $sql2 = $queryBuilder->build($languageCode);

        // execute query and return results
        $nodes = $this->getMapper()->loadBySql2($sql2, $languageCode, $webspaceKey, $queryBuilder->getLimit());

        if ($api) {
            $parentNode = $this->getParentNode($queryBuilder->getParent(), $webspaceKey, $languageCode);
            $result = $this->prepareNode($parentNode, $webspaceKey, $languageCode, 1, false);
            $result['_embedded']['nodes'] = $this->prepareNodesTree($nodes, $webspaceKey, $languageCode, false);
            $result['total'] = sizeof($result['_embedded']['nodes']);
        } else {
            $result = $nodes;
        }

        return $result;
    }

    /**
     * if parent is null return home page else the page with given uuid
     * @param string|null $parent uuid of parent node
     * @param string $webspaceKey
     * @param string $languageCode
     * @return StructureInterface
     */
    private function getParentNode($parent, $webspaceKey, $languageCode)
    {
        if ($parent != null) {
            return $this->getMapper()->load($parent, $webspaceKey, $languageCode);
        } else {
            return $this->getMapper()->loadStartPage($webspaceKey, $languageCode);
        }
    }

    /**
     * @param StructureInterface[] $nodes
     * @param string $webspaceKey
     * @param string $languageCode
     * @param bool $complete
     * @param bool $excludeGhosts
     * @return array
     */
    private function prepareNodesTree($nodes, $webspaceKey, $languageCode, $complete = true, $excludeGhosts = false)
    {
        $results = array();
        foreach ($nodes as $node) {
            $result = $this->prepareNode($node, $webspaceKey, $languageCode, 1, $complete, $excludeGhosts);
            if ($node->getHasChildren() && $node->getChildren() != null) {
                $result['_embedded']['nodes'] = $this->prepareNodesTree(
                    $node->getChildren(),
                    $webspaceKey,
                    $languageCode,
                    $complete,
                    $excludeGhosts
                );
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
        $webspaceKey,
        $languageCode,
        $userId,
        $uuid = null,
        $parentUuid = null,
        $state = null,
        $isShadow = false,
        $shadowBaseLanguage = null
    ) {
        $node = $this->getMapper()->save(
            $data,
            $templateKey,
            $webspaceKey,
            $languageCode,
            $userId,
            true,
            $uuid,
            $parentUuid,
            $state,
            $isShadow,
            $shadowBaseLanguage
        );

        return $this->prepareNode($node, $webspaceKey, $languageCode);
    }

    public function saveNodeRequest(ContentMapperRequest $mapperRequest)
    {
        $node = $this->getMapper()->saveRequest($mapperRequest);

        return $this->prepareNode($node, $mapperRequest->getWebspaceKey(), $mapperRequest->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesTree(
        $uuid,
        $webspaceKey,
        $languageCode,
        $excludeGhosts = false,
        $appendWebspaceNode = false
    ) {
        $nodes = $this->getMapper()->loadTreeByUuid($uuid, $languageCode, $webspaceKey, $excludeGhosts, true);

        if ($appendWebspaceNode) {
            $webspace = $this->webspaceManager->getWebspaceCollection()->getWebspace($webspaceKey);
            $result = array(
                '_embedded' => array(
                    'nodes' => array(
                        array(
                            'id' => $this->sessionManager->getContentNode($webspace->getKey())->getIdentifier(),
                            'path' => '/',
                            'title' => $webspace->getName(),
                            'hasSub' => true,
                            '_embedded' => array(
                                'nodes' => $this->prepareNodesTree(
                                        $nodes,
                                        $webspaceKey,
                                        $languageCode,
                                        false,
                                        $excludeGhosts
                                    )
                            ),
                            '_links' => array(
                                'children' => $this->apiBasePath . '?depth=1&webspace=' . $webspaceKey .
                                    '&language=' . $languageCode . ($excludeGhosts === true ? '&exclude-ghosts=true' : '')
                            )
                        )
                    )
                )
            );
        } else {
            $result = array(
                '_embedded' => array(
                    'nodes' => $this->prepareNodesTree($nodes, $webspaceKey, $languageCode, false, $excludeGhosts)
                )
            );
        }

        // add api links
        $result['_links'] = array(
            'self' => $this->apiBasePath . '/tree?uuid=' . $uuid . '&webspace=' . $webspaceKey . '&language=' .
                $languageCode . ($excludeGhosts === true ? '&exclude-ghosts=true' : '') .
                ($appendWebspaceNode === true ? '&webspace-node=true' : ''),
        );

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function loadExtensionData($uuid, $extensionName, $webspaceKey, $languageCode)
    {
        $structure = $this->getMapper()->load($uuid, $webspaceKey, $languageCode);

        // extract extension
        $extensionData = $structure->getExt();
        $data = $extensionData[$extensionName];

        // add uuid and path
        $data['id'] = $structure->getUuid();
        $data['path'] = $structure->getPath();
        $data['sulu.node.name'] = $structure->getPropertyByTagName('sulu.node.name')->getValue();

        // prepare data
        $data['_links'] = array(
            'self' => $this->apiBasePath . '/' . $uuid . '/' . $extensionName . '?webspace=' . $webspaceKey .
                '&language=' . $languageCode,
        );

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function saveExtensionData($uuid, $data, $extensionName, $webspaceKey, $languageCode, $userId)
    {
        $structure = $this->getMapper()->saveExtension(
            $uuid,
            $data,
            $extensionName,
            $webspaceKey,
            $languageCode,
            $userId
        );

        // extract extension
        $extensionData = $structure->getExt();
        $data = $extensionData[$extensionName];

        // add uuid and path
        $data['id'] = $structure->getUuid();
        $data['path'] = $structure->getPath();
        $data['sulu.node.name'] = $structure->getPropertyByTagName('sulu.node.name')->getValue();

        // prepare data
        $data['_links'] = array(
            'self' => $this->apiBasePath . '/' . $uuid . '/' . $extensionName . '?webspace=' . $webspaceKey .
                '&language=' . $languageCode,
        );

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function moveNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId)
    {
        try {
            // call mapper function
            $structure = $this->getMapper()->move($uuid, $destinationUuid, $userId, $webspaceKey, $languageCode);
        } catch (PHPCRException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        } catch (RepositoryException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        }

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function copyNode($uuid, $destinationUuid, $webspaceKey, $languageCode, $userId)
    {
        try {
            // call mapper function
            $structure = $this->getMapper()->copy($uuid, $destinationUuid, $userId, $webspaceKey, $languageCode);
        } catch (PHPCRException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        } catch (RepositoryException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        }

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    /**
     * {@inheritdoc}
     */
    public function orderBefore($uuid, $beforeUuid, $webspaceKey, $languageCode, $userId)
    {
        try {
            // call mapper function
            $structure = $this->getMapper()->orderBefore($uuid, $beforeUuid, $userId, $webspaceKey, $languageCode);
        } catch (PHPCRException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        } catch (RepositoryException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        }

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }
}
