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

use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * File.
 */
class File implements AuditableInterface
{
    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $version;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $fileVersions;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\Media
     */
    private $media;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $changer;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $creator;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->fileVersions = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Set version.
     *
     * @param int $version
     *
     * @return File
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
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
     * Add fileVersions.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersions
     *
     * @return File
     */
    public function addFileVersion(\Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersions)
    {
        $this->fileVersions[] = $fileVersions;

        return $this;
    }

    /**
     * Remove fileVersions.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersions
     */
    public function removeFileVersion(\Sulu\Bundle\MediaBundle\Entity\FileVersion $fileVersions)
    {
        $this->fileVersions->removeElement($fileVersions);
    }

    /**
     * Get fileVersions.
     *
     * @return FileVersion[]
     */
    public function getFileVersions()
    {
        return $this->fileVersions;
    }

    /**
     * Get latest file version.
     *
     * @return FileVersion[]
     */
    public function getLatestFileVersion()
    {
        /** @var FileVersion $fileVersion */
        foreach ($this->fileVersions as $fileVersion) {
            if ($fileVersion->getVersion() === $this->getVersion()) {
                return $fileVersion;
            }
        }

        return;
    }

    /**
     * Set media.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     *
     * @return File
     */
    public function setMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return File
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return File
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }
}
