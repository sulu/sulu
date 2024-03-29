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
 * FileVersionContentLanguage.
 */
class FileVersionContentLanguage
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $id;

    /**
     * @var FileVersion
     */
    private $fileVersion;

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return FileVersionContentLanguage
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return int
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set fileVersion.
     *
     * @return FileVersionContentLanguage
     */
    public function setFileVersion(?FileVersion $fileVersion = null)
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
            $this->setId(null);
        }
    }
}
