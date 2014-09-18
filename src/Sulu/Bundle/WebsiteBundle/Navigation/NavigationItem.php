<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Navigation;

use Sulu\Component\Content\StructureInterface;

/**
 * Frontend navigation item
 * @package Sulu\Bundle\WebsiteBundle\Navigation
 */
class NavigationItem
{
    /**
     * @var string
     */
    private $id;

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
     * @var integer
     */
    private $nodeType;

    /**
     * @var array
     */
    private $excerpt;

    function __construct($title, $url, $excerpt, $children = array(), $id = null, $nodeType = 1)
    {
        $this->title = $title;
        $this->url = $url;
        $this->nodeType = $nodeType;
        $this->excerpt = $excerpt;

        $this->id = ($id === null ? uniqid() : $id);

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
    public function getId()
    {
        return $this->id;
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
