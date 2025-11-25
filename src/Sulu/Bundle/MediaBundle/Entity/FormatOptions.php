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

class FormatOptions
{
    private int $cropX;

    private int $cropY;

    private int $cropWidth;

    private int $cropHeight;

    private string $formatKey;

    private FileVersion $fileVersion;

    public function setCropX(int|float $cropX): static
    {
        $this->cropX = (int) \round($cropX);

        return $this;
    }

    public function getCropX(): int
    {
        return $this->cropX;
    }

    public function setCropY(int|float $cropY): static
    {
        $this->cropY = (int) \round($cropY);

        return $this;
    }

    public function getCropY(): int
    {
        return $this->cropY;
    }

    public function setCropWidth(int|float $cropWidth): static
    {
        $this->cropWidth = (int) \round($cropWidth);

        return $this;
    }

    public function getCropWidth(): int
    {
        return $this->cropWidth;
    }

    public function setCropHeight(int|float $cropHeight): static
    {
        $this->cropHeight = (int) \round($cropHeight);

        return $this;
    }

    public function getCropHeight(): int
    {
        return $this->cropHeight;
    }

    public function setFormatKey(string $formatKey): static
    {
        $this->formatKey = $formatKey;

        return $this;
    }

    public function getFormatKey(): string
    {
        return $this->formatKey;
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
}
