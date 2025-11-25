<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

class CategoryTranslationMedia
{
    protected int $id;

    protected CategoryTranslationInterface $categoryTranslation;

    protected MediaInterface $media;

    protected int $position = 0;

    public function __construct(CategoryTranslationInterface $categoryTranslation, MediaInterface $media, int $position)
    {
        $this->categoryTranslation = $categoryTranslation;
        $this->media = $media;
        $this->position = $position;
    }

    public function setCategoryTranslation(CategoryTranslationInterface $categoryTranslation): self
    {
        $this->categoryTranslation = $categoryTranslation;

        return $this;
    }

    public function getCategoryTranslation(): CategoryTranslationInterface
    {
        return $this->categoryTranslation;
    }

    public function getMedia(): MediaInterface
    {
        return $this->media;
    }

    public function setMedia(MediaInterface $media): self
    {
        $this->media = $media;

        return $this;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
