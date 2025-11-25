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

class FileVersionMeta
{
    private int $id;

    private string $title;

    private ?string $description = null;

    private ?string $copyright = null;

    private ?string $credits = null;

    private string $locale;

    private FileVersion $fileVersion;

    public function getId(): int
    {
        return $this->id;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setCopyright(?string $copyright): static
    {
        $this->copyright = $copyright;

        return $this;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function setCredits(?string $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function getCredits(): ?string
    {
        return $this->credits;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setFileVersion(FileVersion $fileVersion): static
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    public function getFileVersion(): FileVersion
    {
        return $this->fileVersion;
    }

    /**
     * don't clone id.
     */
    public function __clone()
    {
        if (isset($this->id)) {
            unset($this->id);
        }
    }
}
