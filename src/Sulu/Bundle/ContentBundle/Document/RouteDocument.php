<?php

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;

/**
 * The route document represents a route with in a webspace.
 *
 * Route Documents are children of the designated route-containing
 * node (which is a child of the webspace node).
 *
 * Routes contain a reference to the content which should be dispayed
 * when the route is resolved by the RouteProvider.
 */
class RouteDocument implements
    NodeNameBehavior,
    PathBehavior,
    UuidBehavior,
    RouteBehavior
{
    /**
     * @var string
     */
    private $nodeName;

    /**
     * @var string
     */
    private $path;
    
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var object
     */
    private $targetDocument;

    /**
     * {@inheritDoc}
     */
    public function getNodeName() 
    {
        return $this->nodeName;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath() 
    {
        return $this->path;
    }

    /**
     * {@inheritDoc}
     */
    public function getUuid() 
    {
        return $this->uuid;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetDocument() 
    {
        return $this->targetDocument;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setTargetDocument($targetDocument)
    {
        $this->targetDocument = $targetDocument;
    }
}
