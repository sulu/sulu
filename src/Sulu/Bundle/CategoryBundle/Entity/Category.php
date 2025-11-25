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
 * Category.
 */
class Category implements CategoryInterface
{
    use AuditableTrait;

    protected int $lft = 0;

    protected int $rgt = 0;

    protected int $depth = 0;

    protected int $id;

    protected ?string $key = null;

    protected string $defaultLocale = '';

    protected ?CategoryInterface $parent = null;

    /**
     * @var Collection<int, CategoryTranslationInterface>
     */
    protected Collection $translations;

    /**
     * @var Collection<int, CategoryInterface>
     */
    protected Collection $children;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setLft(int $lft): static
    {
        $this->lft = $lft;

        return $this;
    }

    public function getLft(): int
    {
        return $this->lft;
    }

    public function setRgt(int $rgt): static
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function getRgt(): int
    {
        return $this->rgt;
    }

    public function setDepth(int $depth): static
    {
        $this->depth = $depth;

        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function setDefaultLocale(string $defaultLocale): static
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function setParent(?CategoryInterface $parent = null): static
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?CategoryInterface
    {
        return $this->parent;
    }

    public function addTranslation(CategoryTranslationInterface $translations): static
    {
        $this->translations[] = $translations;

        return $this;
    }

    public function removeTranslation(CategoryTranslationInterface $translations): static
    {
        $this->translations->removeElement($translations);

        return $this;
    }

    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function findTranslationByLocale(?string $locale): ?CategoryTranslationInterface
    {
        return $this->translations->filter(
            function(CategoryTranslationInterface $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first() ?: null;
    }

    public function addChild(CategoryInterface $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    public function removeChild(CategoryInterface $child): static
    {
        $this->children->removeElement($child);

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
}
