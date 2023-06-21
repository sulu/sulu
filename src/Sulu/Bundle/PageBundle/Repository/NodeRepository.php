<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Repository;

use PHPCR\RepositoryException;
use Sulu\Bundle\AdminBundle\UserManager\UserManagerInterface;
use Sulu\Bundle\PageBundle\Content\PageSelectionContainer;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Content\Exception\InvalidOrderPositionException;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Content\Repository\ContentRepository;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

@trigger_deprecation(
    'sulu/sulu',
    '2.0',
    'The "%s" class is deprecated, use data from "%s" instead.',
    NodeRepository::class,
    ContentRepository::class
);

/**
 * repository for node objects.
 *
 * @deprecated
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
     * for returning self link in get action.
     *
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
     * @var ContentQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var ContentQueryExecutorInterface
     */
    private $queryExecutor;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        ContentMapperInterface $mapper,
        SessionManagerInterface $sessionManager,
        UserManagerInterface $userManager,
        WebspaceManagerInterface $webspaceManager,
        ContentQueryBuilderInterface $queryBuilder,
        ContentQueryExecutorInterface $queryExecutor,
        AccessControlManagerInterface $accessControlManager,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->mapper = $mapper;
        $this->sessionManager = $sessionManager;
        $this->userManager = $userManager;
        $this->webspaceManager = $webspaceManager;
        $this->queryBuilder = $queryBuilder;
        $this->queryExecutor = $queryExecutor;
        $this->accessControlManager = $accessControlManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * return content mapper.
     *
     * @return ContentMapperInterface
     */
    protected function getMapper()
    {
        return $this->mapper;
    }

    /**
     * returns user fullName.
     *
     * @param int $id userId
     *
     * @return string
     */
    protected function getFullNameByUserId($id)
    {
        return $this->userManager->getFullNameByUserId($id);
    }

    /**
     * returns finished Node (with _links and _embedded).
     *
     * @param string $webspaceKey
     * @param string $languageCode
     * @param int $depth
     * @param bool $complete
     * @param bool $excludeGhosts
     * @param string|null $extension
     *
     * @return array
     *
     * @deprecated This part should be split into a serialization handler and using the hateoas bundle
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

        // add default embedded property with empty nodes array
        $result['_embedded'] = [];
        $result['_embedded']['pages'] = [];

        // add api links
        $result['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/' . $structure->getUuid() .
                    (null !== $extension ? '/' . $extension : ''),
            ],
            'children' => [
                'href' => $this->apiBasePath . '?parentId=' . $structure->getUuid() . '&depth=' . $depth .
                    '&webspace=' . $webspaceKey . '&language=' . $languageCode .
                    (true === $excludeGhosts ? '&exclude-ghosts=true' : ''),
            ],
        ];

        if ($this->tokenStorage && ($token = $this->tokenStorage->getToken())) {
            $result['_permissions'] = $this->accessControlManager->getUserPermissions(
                new SecurityCondition(
                    'sulu.webspaces.' . $webspaceKey,
                    $languageCode,
                    SecurityBehavior::class,
                    $structure->getUuid()
                ),
                $token->getUser()
            );
        }

        return $result;
    }

    /**
     * @param string $apiBasePath
     */
    public function setApiBasePath($apiBasePath)
    {
        $this->apiBasePath = $apiBasePath;
    }

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
            $result['breadcrumb'] = [];
            foreach ($breadcrumb as $item) {
                $result['breadcrumb'][$item->getDepth()] = $item->toArray();
            }
        }

        return $result;
    }

    public function getIndexNode($webspaceKey, $languageCode)
    {
        $structure = $this->getMapper()->loadStartPage($webspaceKey, $languageCode);

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    public function getReferences($uuid)
    {
        $session = $this->sessionManager->getSession();
        $node = $session->getNodeByIdentifier($uuid);

        return \iterator_to_array($node->getReferences());
    }

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
        $result['_embedded']['pages'] = $this->prepareNodesTree(
            $nodes,
            $webspaceKey,
            $languageCode,
            $complete,
            $excludeGhosts,
            $flat ? 1 : $depth
        );
        $result['total'] = \count($result['_embedded']['pages']);

        return $result;
    }

    public function getNodesByIds(
        $ids,
        $webspaceKey,
        $languageCode
    ) {
        $result = [];
        $idString = '';

        if (!empty($ids)) {
            $container = new PageSelectionContainer(
                $ids,
                $this->queryExecutor,
                $this->queryBuilder,
                [],
                $webspaceKey,
                $languageCode,
                true
            );

            $result = $container->getData();
            $idString = \implode(',', $ids);
        }

        return [
            '_embedded' => [
                'pages' => $result,
            ],
            'total' => \count($result),
            '_links' => [
                'self' => ['href' => $this->apiBasePath . '?ids=' . $idString],
            ],
        ];
    }

    public function getWebspaceNode(
        $webspaceKey,
        $languageCode,
        $depth = 1,
        $excludeGhosts = false
    ) {
        // init result
        $data = [];

        // add default empty embedded property
        $data['_embedded'] = [
            'pages' => [$this->createWebspaceNode($webspaceKey, $languageCode, $depth, $excludeGhosts)],
        ];
        // add api links
        $data['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/entry?depth=' . $depth . '&webspace=' . $webspaceKey .
                    '&language=' . $languageCode . (true === $excludeGhosts ? '&exclude-ghosts=true' : ''),
            ],
        ];

        return $data;
    }

    public function getWebspaceNodes($languageCode)
    {
        // init result
        $data = ['_embedded' => ['pages' => []]];

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            $data['_embedded']['pages'][] = $this->createWebspaceNode($webspace->getKey(), $languageCode, 0);
        }

        // add api links
        $data['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/entry?language=' . $languageCode,
            ],
        ];

        return $data;
    }

    /**
     * Creates a webspace node.
     */
    private function createWebspaceNode(
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
            $embedded = $this->prepareNodesTree($nodes, $webspaceKey, $languageCode, true, $excludeGhosts, $depth);
        } else {
            $embedded = [];
        }

        return [
            'id' => $this->sessionManager->getContentNode($webspace->getKey())->getIdentifier(),
            'path' => '/',
            'title' => $webspace->getName(),
            'hasSub' => true,
            'publishedState' => true,
            '_embedded' => $embedded,
            '_links' => [
                'children' => [
                    'href' => $this->apiBasePath . '?depth=' . $depth . '&webspace=' . $webspaceKey .
                        '&language=' . $languageCode . (true === $excludeGhosts ? '&exclude-ghosts=true' : ''),
                ],
            ],
        ];
    }

    public function getFilteredNodes(
        array $filterConfig,
        $languageCode,
        $webspaceKey,
        $preview = false,
        $api = false,
        $exclude = []
    ) {
        $limit = isset($filterConfig['limitResult']) ? $filterConfig['limitResult'] : null;
        $initParams = ['config' => $filterConfig];
        if ($exclude) {
            $initParams['excluded'] = $exclude;
        }

        $this->queryBuilder->init($initParams);
        $data = $this->queryExecutor->execute(
            $webspaceKey,
            [$languageCode],
            $this->queryBuilder,
            true,
            -1,
            $limit,
            null,
            false
        );

        if ($api) {
            if (isset($filterConfig['dataSource'])) {
                if (null !== $this->webspaceManager->findWebspaceByKey($filterConfig['dataSource'])) {
                    $node = $this->sessionManager->getContentNode($filterConfig['dataSource']);
                } else {
                    $node = $this->sessionManager->getSession()->getNodeByIdentifier($filterConfig['dataSource']);
                }
            } else {
                $node = $this->sessionManager->getContentNode($webspaceKey);
            }

            $parentNode = $this->getParentNode($node->getIdentifier(), $webspaceKey, $languageCode);
            $result = $this->prepareNode($parentNode, $webspaceKey, $languageCode, 1, false);

            $result['_embedded']['pages'] = $data;
            $result['total'] = \count($result['_embedded']['pages']);
        } else {
            $result = $data;
        }

        return $result;
    }

    /**
     * if parent is null return home page else the page with given uuid.
     *
     * @param string|null $parent uuid of parent node
     * @param string $webspaceKey
     * @param string $languageCode
     *
     * @return StructureInterface
     */
    private function getParentNode($parent, $webspaceKey, $languageCode)
    {
        if (null != $parent) {
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
     *
     * @return array
     */
    private function prepareNodesTree(
        $nodes,
        $webspaceKey,
        $languageCode,
        $complete = true,
        $excludeGhosts = false,
        $maxDepth = 1,
        $currentDepth = 0
    ) {
        ++$currentDepth;

        $results = [];
        foreach ($nodes as $node) {
            $result = $this->prepareNode($node, $webspaceKey, $languageCode, 1, $complete, $excludeGhosts);
            if (null !== $maxDepth
                && $currentDepth < $maxDepth
                && $node->getHasChildren()
                && null != $node->getChildren()
            ) {
                $result['_embedded']['pages'] = $this->prepareNodesTree(
                    $node->getChildren(),
                    $webspaceKey,
                    $languageCode,
                    $complete,
                    $excludeGhosts,
                    $maxDepth,
                    $currentDepth
                );
            }
            $results[] = $result;
        }

        return $results;
    }

    public function getNodesTree(
        $uuid,
        $webspaceKey,
        $languageCode,
        $excludeGhosts = false,
        $excludeShadows = false
    ) {
        $nodes = $this->loadNodeAndAncestors($uuid, $webspaceKey, $languageCode, $excludeGhosts, $excludeShadows, true);

        $result = [
            '_embedded' => [
                'pages' => $nodes,
            ],
        ];

        if ($this->tokenStorage && ($token = $this->tokenStorage->getToken())) {
            $result['_permissions'] = $this->accessControlManager->getUserPermissions(
                new SecurityCondition(
                    'sulu.webspaces.' . $webspaceKey
                ),
                $token->getUser()
            );
        }

        // add api links
        $result['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/tree?uuid=' . $uuid . '&webspace=' . $webspaceKey . '&language=' .
                    $languageCode . (true === $excludeGhosts ? '&exclude-ghosts=true' : ''),
            ],
        ];

        return $result;
    }

    /**
     * Load the node and its ancestors and convert them into a HATEOAS representation.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $locale
     * @param bool $excludeGhosts
     * @param bool $excludeShadows
     * @param bool $complete
     *
     * @return array
     */
    private function loadNodeAndAncestors($uuid, $webspaceKey, $locale, $excludeGhosts, $excludeShadows, $complete)
    {
        $descendants = $this->getMapper()->loadNodeAndAncestors(
            $uuid,
            $locale,
            $webspaceKey,
            $excludeGhosts,
            $excludeShadows
        );
        $descendants = \array_reverse($descendants);

        $childTiers = [];
        foreach ($descendants as $descendant) {
            foreach ($descendant->getChildren() as $child) {
                $type = $child->getType();

                if ($excludeShadows && null !== $type && 'shadow' === $type->getName()) {
                    continue;
                }

                if ($excludeGhosts && null !== $type && 'ghost' === $type->getName()) {
                    continue;
                }

                if (!isset($childTiers[$descendant->getUuid()])) {
                    $childTiers[$descendant->getUuid()] = [];
                }
                $childTiers[$descendant->getUuid()][] = $this->prepareNode(
                    $child,
                    $webspaceKey,
                    $locale,
                    1,
                    $complete,
                    $excludeGhosts
                );
            }
        }
        $result = \array_shift($childTiers);

        $this->iterateTiers($childTiers, $result);

        return $result;
    }

    /**
     * Iterate over the ancestor tiers and build up the result.
     *
     * @param array $tiers
     * @param array $result (by rereference)
     */
    private function iterateTiers($tiers, &$result)
    {
        \reset($tiers);
        $uuid = \key($tiers);
        $tier = \array_shift($tiers);

        $found = false;
        $node = null;
        if (\is_array($result)) {
            foreach ($result as &$node) {
                if ($node['id'] === $uuid) {
                    $node['_embedded']['pages'] = $tier;
                    $found = true;
                    break;
                }
            }
        }

        if (!$tiers) {
            return;
        }

        if (!$found) {
            throw new \RuntimeException(
                \sprintf(
                    'Could not find target node in with UUID "%s" in tier. This should not happen.',
                    $uuid
                )
            );
        }

        $this->iterateTiers($tiers, $node['_embedded']['pages']);
    }

    public function loadExtensionData($uuid, $extensionName, $webspaceKey, $languageCode)
    {
        $structure = $this->getMapper()->load($uuid, $webspaceKey, $languageCode);

        // extract extension
        $extensionData = $structure->getExt();
        $data = $extensionData[$extensionName];

        // add uuid and path
        $data['id'] = $structure->getUuid();
        $data['path'] = $structure->getPath();
        $data['url'] = $structure->getResourceLocator();
        $data['publishedState'] = $structure->getPublishedState();
        $data['published'] = $structure->getPublished();

        // prepare data
        $data['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/' . $uuid . '/' . $extensionName . '?webspace=' . $webspaceKey .
                    '&language=' . $languageCode,
            ],
        ];

        return $data;
    }

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
        $data['url'] = $structure->getResourceLocator();

        // prepare data
        $data['_links'] = [
            'self' => [
                'href' => $this->apiBasePath . '/' . $uuid . '/' . $extensionName . '?webspace=' . $webspaceKey .
                    '&language=' . $languageCode,
            ],
        ];

        return $data;
    }

    public function orderAt($uuid, $position, $webspaceKey, $languageCode, $userId)
    {
        try {
            // call mapper function
            $structure = $this->getMapper()->orderAt($uuid, $position, $userId, $webspaceKey, $languageCode);
        } catch (DocumentManagerException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        } catch (RepositoryException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        } catch (InvalidOrderPositionException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        }

        return $this->prepareNode($structure, $webspaceKey, $languageCode);
    }

    public function copyLocale($uuid, $userId, $webspaceKey, $srcLocale, $destLocales)
    {
        @trigger_deprecation(
            'sulu/sulu',
            '2.3',
            'The NodeRepository::copyLocale method is deprecated and will be removed in the future.'
            . ' Use DocumentManagerInterface::copyLocale instead.'
        );

        try {
            // call mapper function
            $structure = $this->getMapper()->copyLanguage($uuid, $userId, $webspaceKey, $srcLocale, $destLocales);
        } catch (RepositoryException $ex) {
            throw new RestException($ex->getMessage(), 1, $ex);
        }

        return $this->prepareNode($structure, $webspaceKey, $srcLocale);
    }
}
