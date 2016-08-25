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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Entity for the format-options of a file-version
 */
class FormatOptions
{

    /**
     * @var integer
     */
    private $cropX;

    /**
     * @var integer
     */
    private $cropY;

    /**
     * @var integer
     */
    private $cropWidth;

    /**
     * @var integer
     */
    private $cropHeight;

    /**
     * @var string
     */
    private $formatKey;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\FileVersion
     */
    private $fileVersion;


    /**
     * Set cropX
     *
     * @param integer $cropX
     *
     * @return FormatOptions
     */
    public function setCropX($cropX)
    {
        $this->cropX = $cropX;

        return $this;
    }

    /**
     * Get cropX
     *
     * @return integer
     */
    public function getCropX()
    {
        return $this->cropX;
    }

    /**
     * Set cropY
     *
     * @param integer $cropY
     *
     * @return FormatOptions
     */
    public function setCropY($cropY)
    {
        $this->cropY = $cropY;

        return $this;
    }

    /**
     * Get cropY
     *
     * @return integer
     */
    public function getCropY()
    {
        return $this->cropY;
    }

    /**
     * Set cropWidth
     *
     * @param integer $cropWidth
     *
     * @return FormatOptions
     */
    public function setCropWidth($cropWidth)
    {
        $this->cropWidth = $cropWidth;

        return $this;
    }

    /**
     * Get cropWidth
     *
     * @return integer
     */
    public function getCropWidth()
    {
        return $this->cropWidth;
    }

    /**
     * Set cropHeight
     *
     * @param integer $cropHeight
     *
     * @return FormatOptions
     */
    public function setCropHeight($cropHeight)
    {
        $this->cropHeight = $cropHeight;

        return $this;
    }

    /**
     * Get cropHeight
     *
     * @return integer
     */
    public function getCropHeight()
    {
        return $this->cropHeight;
    }

    /**
     * Set formatKey
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
     * Get formatKey
     *
     * @return string
     */
    public function getFormatKey()
    {
        return $this->formatKey;
    }

    /**
     * Set fileVersion
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersion
     *
     * @return FormatOptions
     */
    public function setFileVersion(\Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersion)
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    /**
     * Get fileVersion
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\FileVersion
     */
    public function getFileVersion()
    {
        return $this->fileVersion;
    }
}
