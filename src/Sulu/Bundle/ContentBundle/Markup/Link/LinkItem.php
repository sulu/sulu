<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Markup\Link;

/**
 * Represents a single link.
 */
class LinkItem
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
     * @var bool
     */
    private $published;

    /**
     * @param string $id
     * @param string $url
     * @param string $title
     * @param bool $published
     */
    public function __construct($id, $title, $url, $published)
    {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->published = $published;
    }

    /**
     * Returns id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Returns url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Indicates published state of link.
     *
     * @return bool
     */
    public function isPublished()
    {
        return $this->published;
    }
}
