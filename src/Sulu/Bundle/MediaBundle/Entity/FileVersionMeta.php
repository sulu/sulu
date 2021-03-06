<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * FileVersionMeta.
 */
class FileVersionMeta
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $copyright;

    /**
     * @var string|null
     */
    private $credits;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var FileVersion
     */
    private $fileVersion;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return FileVersionMeta
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return FileVersionMeta
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set copyright.
     *
     * @param string|null $copyright
     *
     * @return FileVersionMeta
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     * Get copyright.
     *
     * @return string|null
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set credits.
     *
     * @param string|null $credits
     *
     * @return FileVersionMeta
     */
    public function setCredits($credits)
    {
        $this->credits = $credits;

        return $this;
    }

    /**
     * Get credits.
     *
     * @return string|null
     */
    public function getCredits()
    {
        return $this->credits;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return FileVersionMeta
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set fileVersion.
     *
     * @return FileVersionMeta
     */
    public function setFileVersion(FileVersion $fileVersion)
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    /**
     * Get fileVersion.
     *
     * @return FileVersion
     */
    public function getFileVersion()
    {
        return $this->fileVersion;
    }

    /**
     * don't clone id.
     */
    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
        }
    }
}
