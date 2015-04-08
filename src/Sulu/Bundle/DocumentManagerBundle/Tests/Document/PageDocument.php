<?php

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Document;

use Sulu\Component\DocumentManager\Behavior\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\AutoNameBehavior;
use Sulu\Component\DocumentManager\Behavior\TimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\BlameBehavior;

class PageDocument implements AutoNameBehavior, TimestampBehavior, BlameBehavior
{
    private $title;
    private $parent;
    private $changed;
    private $created;
    private $changer;
    private $creator;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($document)
    {
        $this->parent = $document;
    }

    public function getCreated() 
    {
        return $this->created;
    }

    public function getChanged() 
    {
        return $this->changed;
    }

    public function getCreator() 
    {
        return $this->creator;
    }

    public function getChanger() 
    {
        return $this->changer;
    }
}
