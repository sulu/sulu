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
use PHPCR\SessionInterface;
use Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException;
use Sulu\Component\Content\Exception\ResourceLocatorNotExistsException;
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
     * @param string $portal key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorAlreadyExistsException
     */
    public function save(NodeInterface $contentNode, $path, $portal)
    {
        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);

        // check if route already exists
        if (!$this->isUnique($routes, $path)) {
            $routeNode = $routes->getNode(ltrim($path, '/'));
            if ($routeNode->hasProperty('content') && $routeNode->getPropertyValue('content') == $contentNode) {
                // route already exists and referenced on contentNode
                return;
            } else {
                throw new ResourceLocatorAlreadyExistsException();
            }
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
                    $node->addMixin('mix:referenceable');
                }
            }
        }

        // TODO sulu:route mixin to search faster for route
        // $routeNode->addMixin('sulu:route');
        $node->setProperty('content', $contentNode);
    }

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $portal key of portal
     *
     * @throws \Sulu\Component\Content\Exception\ResourceLocatorNotExistsException
     *
     * @return string path
     */
    public function read(NodeInterface $contentNode, $portal)
    {
        // search for references with name 'content'
        foreach ($contentNode->getReferences('content') as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                // remove last slash from parent path and remove left basePath
                $value = ltrim(rtrim($ref->getParent()->getPath(), '/'), $this->basePath);

                return $value;
            }
        }

        throw new ResourceLocatorNotExistsException();
    }

    /**
     * checks if given path is unique
     * @param string $path
     * @param string $portal key of portal
     * @return bool
     */
    public function unique($path, $portal)
    {
        // TODO portal
        $session = $this->sessionFactory->getSession();
        $routes = $this->getRoutes($session);

        return $this->isUnique($routes, $path);
    }

    /**
     * returns a unique path with "-1" if necessary
     * @param string $path
     * @param string $portal key of portal
     * @return string
     */
    public function getUniquePath($path, $portal)
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
     * @return NodeInterface base node of routes
     */
    private function getRoutes(SessionInterface $session)
    {
        // trailing slash
        return $session->getNode('/' . ltrim($this->basePath, '/'));
    }
}
