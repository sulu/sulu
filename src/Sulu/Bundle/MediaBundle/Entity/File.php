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
     * @var DoctrineCollection
     */
    private $fileVersions;

    /**
     * @var MediaInterface
     */
    private $media;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->fileVersions = new ArrayCollection();
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
    public function setChanged(\DateTime $changed)
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
     * @param FileVersion $fileVersions
     *
     * @return File
     */
    public function addFileVersion(FileVersion $fileVersions)
    {
        $this->fileVersions[] = $fileVersions;

        return $this;
    }

    /**
     * Remove fileVersions.
     *
     * @param FileVersion $fileVersions
     */
    public function removeFileVersion(FileVersion $fileVersions)
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
     * @return FileVersion
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
     * @param MediaInterface $media
     *
     * @return File
     */
    public function setMedia(MediaInterface $media)
    {
        $this->media = $media;

        return $this;
    }

    /**
     * Get media.
     *
     * @return Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return File
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return File
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }
}
