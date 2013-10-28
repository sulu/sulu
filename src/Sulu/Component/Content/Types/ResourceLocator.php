<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types;


use PHPCR\NodeInterface;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\PropertyInterface;

class ResourceLocator extends ComplexContentType
{

    private $basePath = '/cmf/routes';

    protected function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * reads the value for given property out of the database + sets the value of the property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @return mixed
     */
    public function get(NodeInterface $node, PropertyInterface $property)
    {
        // search for references with name 'content'
        foreach ($node->getReferences('content') as $ref) {
            if ($ref instanceof \PHPCR\PropertyInterface) {
                $value = str_replace($this->getBasePath(), '', $ref->getParent()->getPath());
                $property->setValue($value);

                return $value;
            }
        }

        // TODO exception handling
        return null;
    }

    /**
     * save the value from given property
     * @param NodeInterface $node
     * @param PropertyInterface $property
     * @param $value
     * @return mixed
     */
    public function set(NodeInterface $node, PropertyInterface $property, $value)
    {
        $session = $this->getSession();
        $data = $property->getValue();

        // create routepath
        $routePath = $this->getBasePath() . '/' . $data; //TODO configure path
        $routePath = explode('/', $routePath);

        // get root node
        $routeNode = $session->getRootNode();

        foreach ($routePath as $path) {
            if ($path != '') {
                if ($routeNode->hasNode($path)) {
                    $routeNode = $routeNode->getNode($path);
                } else {
                    $routeNode = $routeNode->addNode($path, 'nt:unstructured');
                }
            }
        }

        // TODO sulu:route mixin to search faster for route
        // $routeNode->addMixin('sulu:route');
        $routeNode->setProperty('content', $node);
    }

    /**
     * returns type of ContentType
     * PRE_SAVE or POST_SAVE
     * @return int
     */
    public function getType()
    {
        return ContentTypeInterface::POST_SAVE;
    }
}
