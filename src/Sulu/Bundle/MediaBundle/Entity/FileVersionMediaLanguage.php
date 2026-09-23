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
 * Content language of a file version, independent of the content locale.
 */
class FileVersionMediaLanguage
{
    private int $id;

    private string $language;

    #[Exclude]
    private FileVersion $fileVersion;

    public function __construct(FileVersion $fileVersion, string $language)
    {
        $this->fileVersion = $fileVersion;
        $this->language = $language;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getFileVersion(): FileVersion
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
