<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\Compat\Structure;

/**
 * Frontend navigation item.
 */
class NavigationItem
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $url;

    /**
     * @var NavigationItem[]
     */
    private $children;

    /**
     * @var int
     */
    private $nodeType;

    /**
     * @var array
     */
    private $excerpt;

    public function __construct($title, $url, $excerpt, $children = [], $uuid = null, $nodeType = Structure::STATE_TEST)
    {
        $this->title = $title;
        $this->url = $url;
        $this->nodeType = $nodeType;
        $this->excerpt = $excerpt;

        $this->uuid = ($uuid === null ? uniqid() : $uuid);

        $this->children = $children;
    }

    /**
     * @return array
     */
    public function getExcerpt()
    {
        return $this->excerpt;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function getNodeType()
    {
        return $this->nodeType;
    }

    /**
     * @return \Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem[]
     */
    public function getChildren()
    {
        return $this->children;
    }
}
