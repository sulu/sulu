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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Persistence\Model\AuditableTrait;

class File implements AuditableInterface
{
    use AuditableTrait;

    private int $id;

    private int $version;

    /** @var DoctrineCollection<int, FileVersion> */
    private DoctrineCollection $fileVersions;

    private MediaInterface $media;

    public function __construct()
    {
        $this->fileVersions = new ArrayCollection();
    }

    public function setVersion(int $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function addFileVersion(FileVersion $fileVersions): static
    {
        $this->fileVersions[] = $fileVersions;

        return $this;
    }

    public function removeFileVersion(FileVersion $fileVersions): static
    {
        $this->fileVersions->removeElement($fileVersions);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, FileVersion>
     */
    public function getFileVersions(): DoctrineCollection
    {
        return $this->fileVersions;
    }

    public function getFileVersion(int $version): ?FileVersion
    {
        foreach ($this->fileVersions as $fileVersion) {
            if ($fileVersion->getVersion() === $version) {
                return $fileVersion;
            }
        }

        return null;
    }

    public function getLatestFileVersion(): ?FileVersion
    {
        return $this->getFileVersion($this->version);
    }

    public function setMedia(MediaInterface $media): static
    {
        $this->media = $media;

        return $this;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }
}
