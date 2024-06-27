<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @param string $id
     * @param string $locale
     * @param int $author
     * @param \DateTime $authored
     */
    public function __construct(private $id, private $locale, private $author, private $authored)
    {
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
