<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Teaser;

/**
 * Contains teaser information.
 */
class Teaser
{
    /**
     * @var string|int
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $moreText;

    /**
     * @var int
     */
    private $mediaId;

    /**
     * @var string
     */
    private $url;

    /**
     * @param int|string $id
     * @param string $type
     * @param string $locale
     * @param string $title
     * @param string $description
     * @param string $moreText
     * @param string $url
     * @param int $mediaId
     */
    public function __construct($id, $type, $locale, $title, $description, $moreText, $url, $mediaId)
    {
        $this->id = $id;
        $this->type = $type;
        $this->locale = $locale;
        $this->title = $title;
        $this->description = $description;
        $this->moreText = $moreText;
        $this->url = $url;
        $this->mediaId = $mediaId;
    }

    /**
     * Returns id.
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
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
     * Returns description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns more-text.
     *
     * @return string
     */
    public function getMoreText()
    {
        return $this->moreText;
    }

    /**
     * Returns media-id.
     *
     * @return int
     */
    public function getMediaId()
    {
        return $this->mediaId;
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
}
