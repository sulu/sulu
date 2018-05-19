<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager;

/**
 * Represents the version information on a document.
 */
class Version
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $author;

    /**
     * @var \DateTime
     */
    private $authored;

    /**
     * @param string $id
     * @param string $locale
     * @param int $author
     * @param \DateTime $authored
     */
    public function __construct($id, $locale, $author, $authored)
    {
        $this->id = $id;
        $this->locale = $locale;
        $this->author = $author;
        $this->authored = $authored;
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
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return int
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return \DateTime
     */
    public function getAuthored()
    {
        return $this->authored;
    }
}
