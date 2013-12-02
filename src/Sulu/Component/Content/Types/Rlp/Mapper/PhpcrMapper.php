<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Mapper;


use PHPCR\NodeInterface;
use PHPCR\PropertyInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\NodeHelper;
use PHPCR\Util\PathHelper;
use PHPCR\WorkspaceInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorMovedException;
use Sulu\Component\Content\Exception\ResourceLocatorNotFoundException;
use Sulu\Component\PHPCR\SessionFactory\SessionFactoryInterface;

class PhpcrMapper extends RlpMapper
{

    /**
     * @var SessionFactoryInterface
     */
    private $sessionFactory;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @param SessionFactoryInterface $sessionFactory
     * @param string $basePath basePath of routes in phpcr
     */
    function __construct(SessionFactoryInterface $sessionFactory, $basePath)
    {
        $this->sessionFactory = $sessionFactory;
        $this->basePath = $basePath;
    }

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function save(NodeInterface $contentNode, $path, $portalKey)
    {
        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);

        // check if route already exists
        if ($this->checkResourceLocatorExist($routes, $path, $contentNode)) {
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

        $node->addMixin('sulu:path');
        $node->setProperty('sulu:content', $contentNode);
    }

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException
     *
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $portalKey)
    {
        // TODO portal
        // search for references with name 'content'
        foreach ($contentNode->getReferences('sulu:content') as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                return $this->getResourceLocator($ref->getParent()->getPath());
            }
        }

        throw new ResourceLocatorNotFoundException();
    }

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $portalKey key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotFoundException resourceLocator not found or has no content reference
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorMovedException resourceLocator has been moved
     *
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $portalKey)
    {
        $resourceLocator = ltrim($resourceLocator, '/');

        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);
        if (!$routes->hasNode($resourceLocator)) {
            throw new ResourceLocatorNotFoundException();
        }

        $route = $routes->getNode($resourceLocator);

        if ($route->hasProperty('sulu:content')) {
            /** @var NodeInterface $content */
            $content = $route->getPropertyValue('sulu:content');

            return $content->getIdentifier();
        } elseif ($route->hasProperty('sulu:realpath')) {
            // get path from history node
            /** @var NodeInterface $realPath */
            $realPath = $route->getPropertyValue('sulu:realpath');

            throw new ResourceLocatorMovedException(
                $this->getResourceLocator($realPath->getPath()),
                $realPath->getIdentifier()
            );
        } else {
            throw new ResourceLocatorNotFoundException();
        }
    }

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portalKey key of portal
     * @return bool
     */
    public function unique($path, $portalKey)
    {
        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);

        return $this->isUnique($routes, $path);
    }

    /**
     * returns a unique path with "-1" if necessary
     * @param string $path
     * @param string $portalKey key of portal
     * @return string
     */
    public function getUniquePath($path, $portalKey)
    {
        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);

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
                $i++;
            }

            // result is unique
            return $path . $i;
        }
    }

    /**
     * creates a new resourcelocator and creates the correct history
     * @param string $src old resource locator
     * @param string $dest new resource locator
     * @param string $portalKey key of portal
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function move($src, $dest, $portalKey)
    {
        // get abs path
        $absDestPath = $this->getPath($dest);
        $absSrcPath = $this->getPath($src);

        // init session
        $session = $this->sessionFactory->getSession();
        $rootNode = $session->getRootNode();
        $workspace = $session->getWorkspace();
        $routes = $this->getRoutes($session);

        $routeNode = $routes->getNode(ltrim($src, '/'));
        $contentNode = $routeNode->getPropertyValue('sulu:content');

        // check if route already exists
        if ($this->checkResourceLocatorExist($routes, $dest, $contentNode)) {
            return;
        }

        // create parent node for dest path
        $parentAbsDestPath = PathHelper::normalizePath($absDestPath . '/..');
        $this->createRecursive($parentAbsDestPath, $rootNode);
        $session->save();

        // copy route to new
        $workspace->copy($absSrcPath, $absDestPath);

        // get all old routes (in old route tree)
        $qm = $workspace->getQueryManager();
        $sql = "SELECT *
                FROM [sulu:path]
                WHERE ISDESCENDANTNODE('" . $absSrcPath . "/..')";

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
     * create a node recursivly
     * @param string $path path to node
     * @param NodeInterface $rootNode base node to begin
     */
    private function createRecursive($path, $rootNode)
    {
        $pathParts = explode('/', ltrim($path, '/'));
        $curNode = $rootNode;
        for ($i = 0; $i < sizeof($pathParts); $i++) {
            if ($curNode->hasNode($pathParts[$i])) {
                $curNode = $curNode->getNode($pathParts[$i]);
            } else {
                $curNode = $curNode->addNode($pathParts[$i]);
                $curNode->addMixin('mix:referenceable');
            }
        }
    }

    private function changePathToHistory(NodeInterface $node, SessionInterface $session, $absSrcPath, $absDestPath)
    {
        // get new path node
        $relPath = str_replace($absSrcPath, '', $node->getPath());
        $newPath = PathHelper::normalizePath($absDestPath . $relPath);
        $newPathNode = $session->getNode($newPath);

        // add path history mixin
        $node->addMixin('sulu:history');
        $node->setProperty('sulu:realpath', $newPathNode);

        // remove path mixin
        $node->removeMixin('sulu:path');
        $node->getProperty('sulu:content')->remove();

        // get referenced history
        /** @var PropertyInterface $property */
        foreach ($node->getReferences('sulu:realpath') as $property) {
            $property->getParent()->setProperty('sulu:realpath', $newPathNode);
        }
    }

    private function checkResourceLocatorExist(NodeInterface $routes, $resourceLocator, $contentNode)
    {
        if (!$this->isUnique($routes, $resourceLocator)) {
            $routeNode = $routes->getNode(ltrim($resourceLocator, '/'));
            if ($routeNode->hasProperty('sulu:content') &&
                $routeNode->getPropertyValue('sulu:content') == $contentNode
            ) {
                // route already exists and referenced on contentNode
                return true;
            } else {
                throw new ResourceLocatorAlreadyExistsException();
            }
        }

        return false;
    }

    /**
     * check if path is unique from given $root node
     * @param NodeInterface $root route node
     * @param string $path requested path
     * @return bool path is unique
     */
    private function isUnique(NodeInterface $root, $path)
    {
        // check if root has node
        return !$root->hasNode(ltrim($path, '/'));
    }

    /**
     * returns base node of routes from phpcr
     * @param SessionInterface $session current session
     * @return \PHPCR\NodeInterface base node of routes
     */
    private function getRoutes(SessionInterface $session)
    {
        // trailing slash
        return $session->getNode($this->getPath());
    }

    /**
     * returns the abspath
     * @param string $relPath
     * @return string
     */
    private function getPath($relPath = '')
    {
        return '/' . ltrim($this->basePath, '/') . ($relPath !== '' ? '/' . ltrim($relPath, '/') : '');
    }

    /**
     * @param $path
     * @return string
     */
    private function getResourceLocator($path)
    {
        return substr($path, strlen($this->basePath));
    }
}
