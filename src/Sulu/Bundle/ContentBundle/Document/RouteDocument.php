<?php

namespace Sulu\Bundle\ContentBundle\Document;

use Sulu\Component\DocumentManager\Behavior\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\UuidBehavior;

class RouteDocument implements
    NodeNameBehavior,
    PathBehavior,
    UuidBehavior,
    ParentBehavior
{
    private $nodeName;
    private $path;
    private $uuid;
    private $parent;

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
}
