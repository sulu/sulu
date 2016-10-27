<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * Entity for the format-options of a file-version.
 */
class FormatOptions
{
    /**
     * @var int
     */
    private $cropX;

    /**
     * @var int
     */
    private $cropY;

    /**
     * @var int
     */
    private $cropWidth;

    /**
     * @var int
     */
    private $cropHeight;

    /**
     * @var string
     */
    private $formatKey;

    /**
     * @var FileVersion
     */
    private $fileVersion;

    /**
     * Set cropX.
     *
     * @param int $cropX
     *
     * @return FormatOptions
     */
    public function setCropX($cropX)
    {
        $this->cropX = $cropX;

        return $this;
    }

    /**
     * Get cropX.
     *
     * @return int
     */
    public function getCropX()
    {
        return $this->cropX;
    }

    /**
     * Set cropY.
     *
     * @param int $cropY
     *
     * @return FormatOptions
     */
    public function setCropY($cropY)
    {
        $this->cropY = $cropY;

        return $this;
    }

    /**
     * Get cropY.
     *
     * @return int
     */
    public function getCropY()
    {
        return $this->cropY;
    }

    /**
     * Set cropWidth.
     *
     * @param int $cropWidth
     *
     * @return FormatOptions
     */
    public function setCropWidth($cropWidth)
    {
        $this->cropWidth = $cropWidth;

        return $this;
    }

    /**
     * Get cropWidth.
     *
     * @return int
     */
    public function getCropWidth()
    {
        return $this->cropWidth;
    }

    /**
     * Set cropHeight.
     *
     * @param int $cropHeight
     *
     * @return FormatOptions
     */
    public function setCropHeight($cropHeight)
    {
        $this->cropHeight = $cropHeight;

        return $this;
    }

    /**
     * Get cropHeight.
     *
     * @return int
     */
    public function getCropHeight()
    {
        return $this->cropHeight;
    }

    /**
     * Set formatKey.
     *
     * @param string $formatKey
     *
     * @return FormatOptions
     */
    public function setFormatKey($formatKey)
    {
        $this->formatKey = $formatKey;

        return $this;
    }

    /**
     * Get formatKey.
     *
     * @return string
     */
    public function getFormatKey()
    {
        return $this->formatKey;
    }

    /**
     * Set fileVersion.
     *
     * @param FileVersion $fileVersion
     *
     * @return FormatOptions
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
}
