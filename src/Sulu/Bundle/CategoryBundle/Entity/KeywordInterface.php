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
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * The keywords can describe a category with different words.
 */
interface KeywordInterface extends AuditableInterface
{
    /**
     * Set locale.
     */
    public function setLocale(string $locale): static;

    /**
     * Get locale.
     */
    public function getLocale(): string;

    /**
     * Set keyword.
     */
    public function setKeyword(string $keyword): static;

    /**
     * Get keyword.
     */
    public function getKeyword(): string;

    /**
     * Get id.
     */
    public function getId(): ?int;

    /**
     * Add category-translation.
     */
    public function addCategoryTranslation(CategoryTranslationInterface $categoryTranslation): static;

    /**
     * Remove category-translation.
     */
    public function removeCategoryTranslation(CategoryTranslationInterface $categoryTranslation): static;

    /**
     * Get categories.
     *
     * @return Collection<int, CategoryTranslationInterface>
     */
    public function getCategoryTranslations(): Collection;

    public function isReferencedMultiple(): bool;

    public function isReferenced(): bool;

    public function getCategoryTranslationCount(): int;

    public function equals(self $keyword): bool;
}
