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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableTrait;

/**
 * CategoryTranslation.
 */
class CategoryTranslation implements CategoryTranslationInterface
{
    use AuditableTrait;

    protected string $translation = '';

    protected ?string $description = null;

    /**
     * @var Collection<int, CategoryTranslationMedia>
     */
    protected Collection $medias;

    protected string $locale = '';

    protected ?int $id = null;

    protected CategoryInterface $category;

    /**
     * @var Collection<int, KeywordInterface>
     */
    protected Collection $keywords;

    public function __construct()
    {
        $this->keywords = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function setTranslation(string $translation): static
    {
        $this->translation = $translation;

        return $this;
    }

    public function getTranslation(): string
    {
        return $this->translation;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMedias(): array
    {
        $medias = [];

        foreach ($this->medias as $media) {
            $medias[$media->getPosition()] = $media->getMedia();
        }

        \ksort($medias);

        return \array_values($medias);
    }

    public function setMedias(array $medias): static
    {
        $position = 0;
        foreach ($this->medias as $media) {
            $mediaEntity = $medias[$position] ?? null;
            ++$position;

            if (!$mediaEntity) {
                $this->medias->removeElement($media);

                continue;
            }

            $media->setMedia($mediaEntity);
            $media->setPosition($position);
        }

        for (; $position < \count($medias); ++$position) {
            $media = new CategoryTranslationMedia($this, $medias[$position], $position + 1);
            $this->medias->add($media);
        }

        return $this;
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCategory(CategoryInterface $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }

    public function addKeyword(KeywordInterface $keyword): static
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    public function removeKeyword(KeywordInterface $keyword): static
    {
        $this->keywords->removeElement($keyword);

        return $this;
    }

    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function hasKeyword(KeywordInterface $keyword): bool
    {
        return $this->keywords->exists(
            function($key, KeywordInterface $element) use ($keyword) {
                return $element->equals($keyword);
            }
        );
    }
}
