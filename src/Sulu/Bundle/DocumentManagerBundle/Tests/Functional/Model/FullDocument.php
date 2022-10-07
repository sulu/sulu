<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Functional\Model;

use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ChildrenBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\LocaleBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\PathBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;

/**
 * This functional test document should implement as many behaviors as possible.
 */
class FullDocument implements
    NodeNameBehavior,
    TimestampBehavior,
    ParentBehavior,
    UuidBehavior,
    ChildrenBehavior,
    PathBehavior,
    LocaleBehavior
{
    protected $nodeName;

    protected $created;

    protected $changed;

    protected $creator;

    protected $changer;

    protected $parent;

    protected $uuid;

    protected $children;

    protected $path;

    protected $title;

    protected $body;

    protected $status;

    protected $reference;

    protected $locale;

    protected $originalLocale;

    public function __construct()
    {
        $this->children = new \ArrayIterator();
    }

    public function getNodeName()
    {
        return $this->nodeName;
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

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title): void
    {
        $this->title = $title;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body): void
    {
        $this->body = $body;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getReference()
    {
        return $this->reference;
    }

    public function setReference($reference): void
    {
        $this->reference = $reference;
    }

    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    public function setOriginalLocale($originalLocale): void
    {
        $this->originalLocale = $originalLocale;
    }
}
