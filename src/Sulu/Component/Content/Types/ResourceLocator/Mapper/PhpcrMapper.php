<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Mapper;

use DateTime;
use PHPCR\ItemExistsException;
use PHPCR\NodeInterface;
use PHPCR\PathNotFoundException;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Component\Content\Document\Behavior\ResourceSegmentBehavior;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\Content\Types\ResourceLocator\ResourceLocatorInformation;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

/**
 * Manages resource-locators in phpcr.
 */
class PhpcrMapper implements ResourceLocatorMapperInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    /**
     * @param SessionManagerInterface $sessionManager
     * @param DocumentManagerInterface $documentManager
     * @param DocumentInspector $documentInspector
     */
    public function __construct(
        SessionManagerInterface $sessionManager,
        DocumentManagerInterface $documentManager,
        DocumentInspector $documentInspector
    ) {
        $this->sessionManager = $sessionManager;
        $this->documentManager = $documentManager;
        $this->documentInspector = $documentInspector;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ResourceSegmentBehavior $document)
    {
        $path = $document->getResourceSegment();

        $webspaceKey = $this->documentInspector->getWebspace($document);
        $locale = $this->documentInspector->getOriginalLocale($document);
        $segmentKey = null;
        $webspaceRouteRootPath = $this->getWebspaceRouteNodeBasePath($webspaceKey, $locale, $segmentKey);

        try {
            $routeNodePath = $this->loadByContent(
                $this->documentInspector->getNode($document),
                $webspaceKey,
                $locale,
                $segmentKey
            );

            $routeDocument = $this->documentManager->find(
                $webspaceRouteRootPath . $routeNodePath,
                $locale,
                ['rehydrate' => false]
            );
            $routeDocumentPath = $webspaceRouteRootPath . $routeNodePath;
        } catch (ResourceLocatorNotFoundException $e) {
            $routeDocument = $this->documentManager->create('route');
            $routeDocumentPath = $webspaceRouteRootPath . $path;
        }

        $routeDocument->setTargetDocument($document);

        try {
            $this->documentManager->persist(
                $routeDocument,
                $locale,
                [
                    'path' => $routeDocumentPath,
                    'auto_create' => true,
                    'override' => true,
                ]
            );
            $this->documentManager->publish($routeDocument, $locale);
        } catch (ItemExistsException $e) {
            throw new ResourceLocatorAlreadyExistsException($document->getResourceSegment(), $routeDocumentPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $result = $this->iterateRouteNodes(
            $contentNode,
            function ($resourceLocator, \PHPCR\NodeInterface $node) {
                if (false === $node->getPropertyValue('sulu:history') && false !== $resourceLocator) {
                    return $resourceLocator;
                }

                return false;
            },
            $webspaceKey,
            $languageCode,
            $segmentKey
        );

        if ($result !== null) {
            return $result;
        }

        throw new ResourceLocatorNotFoundException();
    }

    /**
     * Iterates over all route nodes assigned by the given node, and executes the callback on it.
     *
     * @param NodeInterface $node
     * @param callable $callback will be called foreach route node (stops and return value if not false)
     * @param string $webspaceKey
     * @param string $languageCode
     * @param string $segmentKey
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

        $routePath = $this->sessionManager->getRoutePath($webspaceKey, $languageCode);

        // search for references with name 'content'
        foreach ($node->getReferences('sulu:content') as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $routeNode = $ref->getParent();
                if (0 !== strpos($routeNode->getPath(), $routePath)) {
                    continue;
                }

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
     * {@inheritdoc}
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

        if (!$pathNode) {
            return $result;
        }

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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function unique($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $routes = $this->getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey);

        return $this->isUnique($routes, $path);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function deleteByPath($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        if (!is_string($path) || trim($path, '/') == '') {
            throw new \InvalidArgumentException(
                sprintf('The path to delete must be a non-empty string, "%s" given.', $path)
            );
        }

        $routeDocument = $this->documentManager->find(
            $this->getPath($path, $webspaceKey, $languageCode, $segmentKey),
            $languageCode
        );

        $this->documentManager->remove($routeDocument);
    }

    /**
     * {@inheritdoc}
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
     * Check if path is unique from given $root node.
     *
     * @param NodeInterface $root route node
     * @param string $path requested path
     *
     * @return bool path is unique
     */
    private function isUnique(NodeInterface $root, $path)
    {
        // check if root has node
        return !$root->hasNode(ltrim($path, '/'));
    }

    /**
     * Returns base node of routes from phpcr.
     *
     * @param string $webspaceKey  current session
     * @param string $languageCode
     * @param string $segmentKey
     *
     * @return \PHPCR\NodeInterface base node of routes
     */
    private function getWebspaceRouteNode($webspaceKey, $languageCode, $segmentKey)
    {
        return $this->sessionManager->getRouteNode($webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * Returns base path of routes from phpcr.
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
     * Returns the abspath.
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
     * Returns resource-locator.
     *
     * @param string $path
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
