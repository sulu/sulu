<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ContentType;

use PHPCR\NodeInterface;

/**
 * Handles the storage of a resource locator of a node
 * @package Sulu\Bundle\ContentBundle\ContentType
 */
class ResourceLocator implements ContentTypeInterface
{
    public function save(NodeInterface $node, $data)
    {
        $session = $node->getSession();
        $routePath = 'cmf/routes/'.$data; //TODO configure path
        $routePath = explode('/', $routePath);
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

        $routeNode->setProperty('content', $node);

        $session->save();
    }
}
