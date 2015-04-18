<?php

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\DocumentManager\Behavior\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\UuidBehavior;
use Sulu\Component\Content\Document\Behavior\RouteBehavior;

class RouteDocument implements
    NodeNameBehavior,
    PathBehavior,
    UuidBehavior,
    RouteBehavior
{
    private $nodeName;
    private $path;
    private $uuid;
    private $targetDocument;

    public function getNodeName() 
    {
        return $this->nodeName;
    }

    public function getPath() 
    {
        return $this->path;
    }

    public function getUuid() 
    {
        return $this->uuid;
    }

    public function getTargetDocument() 
    {
        return $this->targetDocument;
    }
    
    public function setTargetDocument($targetDocument)
    {
        $this->targetDocument = $targetDocument;
    }
    
}
