<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Document;

use PHPCR\NodeInterface;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Component\Content\Document\Behavior\BlameBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\NodeNameBehavior;
use Sulu\Component\DocumentManager\Behavior\Mapping\ParentBehavior;
use Sulu\Component\DocumentManager\Behavior\Path\AutoNameBehavior;

/**
 * Contains information about custom-urls and the relations to the routes.
 */
class CustomUrlDocument implements
    CustomUrlBehavior,
    NodeNameBehavior,
    TimestampBehavior,
    BlameBehavior,
    ParentBehavior,
    AutoNameBehavior
{
    public const RESOURCE_KEY = 'custom_urls';

    /**
     * @var string
     */
    protected $uuid;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var bool
     */
    protected $published;

    /**
     * @var string
     */
    protected $baseDomain;

    /**
     * @var array
     */
    protected $domainParts;

    /**
     * @var PageDocument
     */
    protected $targetDocument;

    /**
     * @var string
     */
    protected $originalLocale;

    /**
     * @var string
     */
    protected $targetLocale;

    /**
     * @var bool
     */
    protected $canonical;

    /**
     * @var bool
     */
    protected $redirect;

    /**
     * @var bool
     */
    protected $noFollow;

    /**
     * @var bool
     */
    protected $noIndex;

    /**
     * @var RouteDocument
     */
    protected $routes;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var int
     */
    protected $creator;

    /**
     * @var int
     */
    protected $changer;

    /**
     * @var NodeInterface
     */
    protected $parent;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $nodeName;

    public function __construct()
    {
        $this->routes = [];
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function isPublished()
    {
        return $this->published;
    }

    /**
     * Set published state.
     *
     * @param bool $published
     */
    public function setPublished($published)
    {
        $this->published = $published;
    }

    public function getBaseDomain()
    {
        return $this->baseDomain;
    }

    /**
     * Set base domain.
     *
     * @param string $baseDomain
     */
    public function setBaseDomain($baseDomain)
    {
        $this->baseDomain = $baseDomain;
    }

    public function getDomainParts()
    {
        return $this->domainParts;
    }

    /**
     * Set domain parts.
     *
     * @param array $domainParts
     */
    public function setDomainParts($domainParts)
    {
        $this->domainParts = $domainParts;
    }

    public function getTargetDocument()
    {
        return $this->targetDocument;
    }

    public function setTargetDocument($targetDocument)
    {
        $this->targetDocument = $targetDocument;
    }

    public function getOriginalLocale()
    {
        return $this->originalLocale;
    }

    public function setOriginalLocale($originalLocale)
    {
        $this->originalLocale = $originalLocale;
    }

    public function getTargetLocale()
    {
        return $this->targetLocale;
    }

    /**
     * Set target locale.
     *
     * @param string $targetLocale
     */
    public function setTargetLocale($targetLocale)
    {
        $this->targetLocale = $targetLocale;
    }

    public function isCanonical()
    {
        return $this->canonical;
    }

    /**
     * Set canonical.
     *
     * @param bool $canonical
     */
    public function setCanonical($canonical)
    {
        $this->canonical = $canonical;
    }

    public function isRedirect()
    {
        return $this->redirect;
    }

    /**
     * Set redirect.
     *
     * @param bool $redirect
     */
    public function setRedirect($redirect)
    {
        $this->redirect = $redirect;
    }

    public function isNoFollow()
    {
        return $this->noFollow;
    }

    /**
     * @param bool $noFollow
     */
    public function setNoFollow($noFollow)
    {
        $this->noFollow = $noFollow;
    }

    public function isNoIndex()
    {
        return $this->noIndex;
    }

    /**
     * @param bool $noIndex
     */
    public function setNoIndex($noIndex)
    {
        $this->noIndex = $noIndex;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
    }

    public function addRoute($route, RouteDocument $routeDocument)
    {
        $this->routes[$route] = $routeDocument;
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

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getPath()
    {
        return $this->path;
    }
}
