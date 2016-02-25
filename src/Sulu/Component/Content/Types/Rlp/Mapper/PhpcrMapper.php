<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;

use DateTime;
use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\Rlp\ResourceLocatorInformation;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

class PhpcrMapper extends RlpMapper
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @param SessionManagerInterface $sessionManager
     */
    public function __construct(SessionManagerInterface $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    /**
     * creates a new route for given path.
     *
     * @param NodeInterface $contentNode  reference node
     * @param string        $path         path to generate
     * @param string        $webspaceKey  key of webspace
     * @param string        $languageCode
     * @param string        $segmentKey
     */
    public function save(NodeInterface $contentNode, $path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        // check if route already exists
        if ($this->checkResourceLocator($routes, $path, $contentNode)) {
            return;
        }

        // create root recursive
        $routePath = explode('/', ltrim($path, '/'));
        $node = $routes;
        foreach ($routePath as $path) {
            if ($path != '') {
                if ($node->hasNode($path)) {
                    $node = $node->getNode($path);
                } else {
                    $node = $node->addNode($path, 'nt:unstructured');
                }
            }
        }
        $this->sessionManager->getSession()->save();

        $node->addMixin('sulu:path');
        $node->setProperty('sulu:content', $contentNode);
        $node->setProperty('sulu:history', false);
        $node->setProperty('sulu:created', new DateTime());
    }

    /**
     * returns path for given contentNode.
     *
     * @param NodeInterface $contentNode  reference node
     * @param string        $webspaceKey  key of portal
     * @param string        $languageCode
     * @param string        $segmentKey
     *
     * @throws ResourceLocatorNotFoundException
     *
     * @return NodeInterface path
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $result = $this->iterateRouteNodes(
            $contentNode,
            function ($resourceLocator, \PHPCR\NodeInterface $node) {
                if (false === $node->getPropertyValue('sulu:history') && false !== $resourceLocator) {
                    return $resourceLocator;
                } else {
                    return false;
                }
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        if ($result !== null) {
            return $result;
        } else {
            throw new ResourceLocatorNotFoundException();
        }
    }

    /**
     * Iterates over all route nodes assigned by the given node, and executes the callback on it.
     *
     * @param NodeInterface $node
     * @param callable      $callback     will be called foreach route node (stops and return value if not false)
     * @param string        $webspaceKey
     * @param string        $languageCode
     * @param string        $segmentKey
     *
     * @return \PHPCR\NodeInterface
     */
    private function iterateRouteNodes(
        NodeInterface $node,
        $callback,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        if ($node->isNew()) {
            return;
        }
        // search for references with name 'content'
        foreach ($node->getReferences('sulu:content') as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $routeNode = $ref->getParent();

                $resourceLocator = $this->getResourceLocator(
                    $ref->getParent()->getPath(),
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                );

                $result = $callback($resourceLocator, $routeNode);
                if (false !== $result) {
                    return $result;
                }
            }
        }

        return;
    }

    /**
     * returns path for given contentNode.
     *
     * @param string $uuid         uuid of contentNode
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws ResourceLocatorNotFoundException
     *
     * @return NodeInterface path
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        return $this->loadByContent($contentNode, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function loadHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // get content node
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);

        // get current path node
        $pathNode = $this->iterateRouteNodes(
            $contentNode,
            function ($resourceLocator, \PHPCR\NodeInterface $node) use (&$result) {
                if (false === $node->getPropertyValue('sulu:history') && false !== $resourceLocator) {
                    return $node;
                } else {
                    return false;
                }
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        // iterate over history of path node
        $result = [];
        $this->iterateRouteNodes(
            $pathNode,
            function ($resourceLocator, NodeInterface $node) use (&$result) {
                if (false !== $resourceLocator) {
                    // add resourceLocator
                    $result[] = new ResourceLocatorInformation(
                    //backward compability
                        $resourceLocator,
                        $node->getPropertyValueWithDefault('sulu:created', new DateTime()),
                        $node->getIdentifier()
                    );
                }

                return false;
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        // sort history descending
        usort(
            $result,
            function (ResourceLocatorInformation $item1, ResourceLocatorInformation $item2) {
                return $item1->getCreated() < $item2->getCreated();
            }
        );

        return $result;
    }

    /**
     * returns the uuid of referenced content node.
     *
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey     key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws ResourceLocatorMovedException    resourceLocator has been moved
     * @throws ResourceLocatorNotFoundException resourceLocator not found or has no content reference
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $resourceLocator = ltrim($resourceLocator, '/');

        try {
            $path = sprintf(
                '%s/%s',
                $this->getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey),
                $resourceLocator
            );
            if ($resourceLocator !== '') {
                // get requested resource locator route node
                $route = $this->sessionManager->getSession()->getNode($path);
            } else {
                // get home page route node
                $route = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);
            }
        } catch (PathNotFoundException $e) {
            throw new ResourceLocatorNotFoundException(sprintf('Path "%s" not found', $path), null, $e);
        }

        if ($route->hasProperty('sulu:content') && $route->hasProperty('sulu:history')) {
            if (!$route->getPropertyValue('sulu:history')) {
                /** @var NodeInterface $content */
                $content = $route->getPropertyValue('sulu:content');

                return $content->getIdentifier();
            } else {
                // get path from history node
                /** @var NodeInterface $realPath */
                $realPath = $route->getPropertyValue('sulu:content');

                throw new ResourceLocatorMovedException(
                    $this->getResourceLocator($realPath->getPath(), $webspaceKey, $languageCode, $segmentKey),
                    $realPath->getIdentifier()
                );
            }
        } else {
            throw new ResourceLocatorNotFoundException(sprintf(
                'Route has "%s" does not have either the "sulu:content" or "sulu:history" properties',
                $route->getPath()
            ));
        }
    }

    /**
     * checks if given path is unique.
     *
     * @param string $path
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return bool
     */
    public function unique($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        return $this->isUnique($routes, $path);
    }

    /**
     * returns a unique path with "-1" if necessary.
     *
     * @param string $path
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    public function getUniquePath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        if ($this->isUnique($routes, $path)) {
            // path is already unique
            return $path;
        } else {
            // append -
            $path .= '-';
            // init counter
            $i = 1;
            // while $path-$i is not unique raise counter
            while (!$this->isUnique($routes, $path . $i)) {
                ++$i;
            }

            // result is unique
            return $path . $i;
        }
    }

    /**
     * creates a new resourcelocator and creates the correct history.
     *
     * @param string $src          old resource locator
     * @param string $dest         new resource locator
     * @param string $webspaceKey  key of portal
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @throws ResourceLocatorMovedException
     * @throws ResourceLocatorNotFoundException
     */
    public function move($src, $dest, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // get abs path
        $absDestPath = $this->getPath($dest, $webspaceKey, $languageCode, $segmentKey);
        $absSrcPath = $this->getPath($src, $webspaceKey, $languageCode, $segmentKey);

        // init session
        $session = $this->sessionManager->getSession();
        $rootNode = $session->getRootNode();
        $workspace = $session->getWorkspace();
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        $routeNode = $routes->getNode(ltrim($src, '/'));
        if (!$routeNode->hasProperty('sulu:content')) {
            throw new ResourceLocatorNotFoundException();
        } elseif ($routeNode->getPropertyValue('sulu:history')) {
            $realPath = $routeNode->getPropertyValue('sulu:content');

            throw new ResourceLocatorMovedException(
                $this->getResourceLocator($realPath->getPath(), $webspaceKey, $languageCode, $segmentKey),
                $realPath->getIdentifier()
            );
        }

        $contentNode = $routeNode->getPropertyValue('sulu:content');

        // check if route already exists
        if ($this->checkResourceLocator($routes, $dest, $contentNode)) {
            return;
        }

        // create parent node for dest path
        $parentAbsDestPath = PathHelper::normalizePath($absDestPath . '/..');
        $this->createRecursive($parentAbsDestPath, $rootNode);
        // TODO remove this save as soon as possible
        $session->save();

        // copy route to new
        $workspace->copy($absSrcPath, $absDestPath);
        $destNode = $routes->getNode(ltrim($dest, '/'));
        $destNode->setProperty('sulu:created', new DateTime());

        // change old route node to history
        $this->changePathToHistory($routeNode, $session, $absSrcPath, $absDestPath);

        // get all old routes (in old route tree)
        $qm = $workspace->getQueryManager();
        $sql = "SELECT *
                FROM [nt:unstructured]
                WHERE ISDESCENDANTNODE('" . $absSrcPath . "')
                AND [jcr:mixinTypes] = 'sulu:path'";

        $query = $qm->createQuery($sql, 'JCR-SQL2');
        $result = $query->execute();

        /** @var NodeInterface $node */
        foreach ($result->getNodes() as $node) {
            // FIXME move in SQL statement?
            if ($node->getPath() != $absDestPath) {
                $this->changePathToHistory($node, $session, $absSrcPath, $absDestPath);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        if (!is_string($path) || trim($path, '/') == '') {
            throw new \InvalidArgumentException(
                sprintf('The path to delete must be a non-empty string, "%s" given.', $path)
            );
        }

        $session = $this->sessionManager->getSession();
        $routeNode = $session->getNode($this->getPath($path, $webspaceKey, $languageCode, $segmentKey));
        $this->deleteByNode($routeNode, $session, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    private function deleteByNode(
        NodeInterface $node,
        SessionInterface $session,
        $webspaceKey,
        $languageCode,
        $segmentKey = null
    ) {
        if ($node->getPropertyValue('sulu:history') !== true) {
            // search for history nodes
            $this->iterateRouteNodes(
                $node,
                function ($resourceLocator, NodeInterface $historyNode) use (
                    $session,
                    $webspaceKey,
                    $languageCode,
                    $segmentKey
                ) {
                    // delete history nodes
                    $this->deleteByNode($historyNode, $session, $webspaceKey, $languageCode, $segmentKey);
                },
                $webspaceKey,
                $languageCode,
                $segmentKey
            );
        }
        $node->remove();
    }

    /**
     * returns resource locator for parent node.
     *
     * @param string $uuid
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return NodeInterface|null
     */
    public function getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $session = $this->sessionManager->getSession();
        $contentNode = $session->getNodeByIdentifier($uuid);
        $parentNode = $contentNode->getParent();

        try {
            return $this->loadByContent($parentNode, $webspaceKey, $languageCode, $segmentKey);
        } catch (ResourceLocatorNotFoundException $ex) {
            // parent node don´t have a resource locator
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restoreByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $rootNode = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);
        $newRouteNode = $rootNode->getNode(ltrim($path, '/'));
        $currentRouteNode = $newRouteNode->getPropertyValue('sulu:content');
        $contentNode = $currentRouteNode->getPropertyValue('sulu:content');

        // change other history connections
        $this->iterateRouteNodes(
            $currentRouteNode,
            function ($resourceLocator, NodeInterface $node) use (&$newRouteNode) {
                if ($node->getPropertyValue('sulu:history') === true) {
                    $node->setProperty('sulu:content', $newRouteNode);
                }

                return false;
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        // change history
        $newRouteNode->setProperty('sulu:history', false);
        $currentRouteNode->setProperty('sulu:history', true);

        // set creation date
        $newRouteNode->setProperty('sulu:created', new DateTime());
        $currentRouteNode->setProperty('sulu:created', new DateTime());

        // set content
        $newRouteNode->setProperty('sulu:content', $contentNode);
        $currentRouteNode->setProperty('sulu:content', $newRouteNode);

        // save session
        $this->sessionManager->getSession()->save();
    }

    /**
     * create a node recursively.
     *
     * @param string        $path     path to node
     * @param NodeInterface $rootNode base node to begin
     */
    private function createRecursive($path, $rootNode)
    {
        $pathParts = explode('/', ltrim($path, '/'));
        $curNode = $rootNode;
        for ($i = 0; $i < count($pathParts); ++$i) {
            if ($curNode->hasNode($pathParts[$i])) {
                $curNode = $curNode->getNode($pathParts[$i]);
            } else {
                $curNode = $curNode->addNode($pathParts[$i]);
                $curNode->addMixin('mix:referenceable');
            }
        }
    }

    /**
     * changes path node to history node.
     *
     * @param NodeInterface    $node
     * @param SessionInterface $session
     * @param string           $absSrcPath
     * @param string           $absDestPath
     */
    private function changePathToHistory(NodeInterface $node, SessionInterface $session, $absSrcPath, $absDestPath)
    {
        // get new path node
        $relPath = str_replace($absSrcPath, '', $node->getPath());
        $newPath = PathHelper::normalizePath($absDestPath . $relPath);
        $newPathNode = $session->getNode($newPath);

        // set history to true and set content to new path
        $node->setProperty('sulu:content', $newPathNode);
        $node->setProperty('sulu:history', true);

        // get referenced history
        /** @var PropertyInterface $property */
        foreach ($node->getReferences('sulu:content') as $property) {
            $property->getParent()->setProperty('sulu:content', $newPathNode);
        }
    }

    /**
     * check resourcelocator is unique and points to given content node.
     *
     * @param NodeInterface $routes
     * @param string        $resourceLocator
     * @param $contentNode
     *
     * @return bool
     *
     * @throws ResourceLocatorAlreadyExistsException
     */
    private function checkResourceLocator(NodeInterface $routeNode, $resourceLocator, $contentNode)
    {
        if (!$this->isUnique($routeNode, $resourceLocator)) {
            $routeNode = $routeNode->getNode(ltrim($resourceLocator, '/'));
            if ($routeNode->hasProperty('sulu:content') &&
                $routeNode->getPropertyValue('sulu:content') == $contentNode
            ) {
                // route already exists and referenced on contentNode
                return true;
            } else {
                throw new ResourceLocatorAlreadyExistsException($resourceLocator, $routeNode->getPath());
            }
        }

        return false;
    }

    /**
     * check if path is unique from given $root node.
     *
     * @param NodeInterface $root route node
     * @param string        $path requested path
     *
     * @return bool path is unique
     */
    private function isUnique(NodeInterface $root, $path)
    {
        // check if root has node
        return !$root->hasNode(ltrim($path, '/'));
    }

    /**
     * returns base node of routes from phpcr.
     *
     * @param string $webspaceKey  current session
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return \PHPCR\NodeInterface base node of routes
     */
    private function getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey)
    {
        // trailing slash
        return $this->sessionManager->getRouteNode($webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns base path of routes from phpcr.
     *
     * @param string $webspaceKey  current session
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    private function getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey)
    {
        return $this->sessionManager->getRoutePath($webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns the abspath.
     *
     * @param string $relPath
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    private function getPath($relPath, $webspaceKey, $languageCode, $segmentKey)
    {
        $basePath = $this->getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey);

        return '/' . ltrim($basePath, '/') . ($relPath !== '' ? '/' . ltrim($relPath, '/') : '');
    }

    /**
     * @param $path
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return string
     */
    private function getResourceLocator($path, $webspaceKey, $languageCode, $segmentKey)
    {
        $basePath = $this->getWebspaceRouteNodeBasePath($webspaceKey, $languageCode, $segmentKey);
        if ($path === $basePath) {
            return '/';
        }
        if (false !== strpos($path, $basePath . '/')) {
            $result = str_replace($basePath . '/', '/', $path);
            if (0 === strpos($result, '/')) {
                return $result;
            }
        }

        return false;
    }
}
