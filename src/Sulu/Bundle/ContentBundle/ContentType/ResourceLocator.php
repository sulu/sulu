<?php
/**
 * Created by IntelliJ IDEA.
 * User: danielrotter
 * Date: 14.10.13
 * Time: 22:41
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\ContentBundle\ContentType;


use PHPCR\NodeInterface;

class ResourceLocator implements ContentTypeInterface
{

    public function save(NodeInterface $node, $data)
    {
        $session = $node->getSession();
        $routePath = 'cmf/routes/'.$data;
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
