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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Interface for the extensible CategoryTranslation entity.
 */
interface CategoryTranslationInterface extends AuditableInterface
{
    /**
     * Set translation.
     */
    public function setTranslation(string $translation): static;

    /**
     * Get translation.
     */
    public function getTranslation(): string;

    /**
     * Get description.
     */
    public function getDescription(): ?string;

    /**
     * Set description.
     */
    public function setDescription(?string $description): static;

    /**
     * Get medias.
     *
     * @return MediaInterface[]
     */
    public function getMedias(): array;

    /**
     * Set medias.
     *
     * @param MediaInterface[] $medias
     */
    public function setMedias(array $medias): static;

    /**
     * Set locale.
     */
    public function setLocale(string $locale): static;

    /**
     * Get locale.
     */
    public function getLocale(): string;

    /**
     * Get id.
     */
    public function getId(): ?int;

    /**
     * Set category.
     */
    public function setCategory(CategoryInterface $category): static;

    /**
     * Get category.
     */
    public function getCategory(): CategoryInterface;

    /**
     * Add keyword.
     */
    public function addKeyword(KeywordInterface $keyword): static;

    /**
     * Remove keyword.
     */
    public function removeKeyword(KeywordInterface $keyword): static;

    /**
     * Get keywords.
     *
     * @return Collection<int, KeywordInterface>
     */
    public function getKeywords(): Collection;

    /**
     * Returns true if given keyword already linked with the category.
     */
    public function hasKeyword(KeywordInterface $keyword): bool;
}
