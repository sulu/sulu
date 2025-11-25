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
 * The keywords can describe a category with different words.
 */
class Keyword implements KeywordInterface
{
    use AuditableTrait;

    protected int $id;

    protected string $keyword = '';

    protected string $locale = '';

    /**
     * @var Collection<int, CategoryTranslationInterface>
     */
    protected Collection $categoryTranslations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->categoryTranslations = new ArrayCollection();
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

    public function setKeyword(string $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function equals(KeywordInterface $keyword): bool
    {
        return $keyword->getKeyword() === $this->getKeyword()
        && $keyword->getLocale() === $this->getLocale();
    }

    public function addCategoryTranslation(CategoryTranslationInterface $categoryTranslation): static
    {
        $this->categoryTranslations[] = $categoryTranslation;

        return $this;
    }

    public function removeCategoryTranslation(CategoryTranslationInterface $categoryTranslation): static
    {
        $this->categoryTranslations->removeElement($categoryTranslation);

        return $this;
    }

    public function getCategoryTranslations(): Collection
    {
        return $this->categoryTranslations;
    }

    public function isReferencedMultiple(): bool
    {
        return $this->getCategoryTranslations()->count() > 1;
    }

    public function isReferenced(): bool
    {
        return $this->getCategoryTranslations()->count() > 0;
    }

    public function getCategoryTranslationCount(): int
    {
        return $this->getCategoryTranslations()->count();
    }
}
