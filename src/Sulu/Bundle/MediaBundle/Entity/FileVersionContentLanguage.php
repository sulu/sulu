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

use JMS\Serializer\Annotation\Exclude;

/**
 * Content locale of a file version, independent of the locale the media is edited in.
 */
class FileVersionContentLanguage
{
    private int $id;

    private string $locale;

    #[Exclude]
    private ?FileVersion $fileVersion;

    public function __construct(FileVersion $fileVersion, string $locale)
    {
        $this->fileVersion = $fileVersion;
        $this->locale = $locale;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getFileVersion(): ?FileVersion
    {
        return $this->fileVersion;
    }

    public function setFileVersion(FileVersion $fileVersion): static
    {
        $this->fileVersion = $fileVersion;

        return $this;
    }

    public function __clone()
    {
        if (isset($this->id)) {
            unset($this->id);
        }
    }
}
