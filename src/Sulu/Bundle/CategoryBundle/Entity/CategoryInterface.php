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
 * Interface for the extensible Category entity.
 */
interface CategoryInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'categories';

    /**
     * Set id.
     */
    public function setId(int $id): static;

    /**
     * Set lft.
     */
    public function setLft(int $lft): static;

    /**
     * Get lft.
     */
    public function getLft(): int;

    /**
     * Set rgt.
     */
    public function setRgt(int $rgt): static;

    /**
     * Get rgt.
     */
    public function getRgt(): int;

    /**
     * Set depth.
     */
    public function setDepth(int $depth): static;

    /**
     * Get depth.
     */
    public function getDepth(): int;

    /**
     * Get key.
     */
    public function getKey(): ?string;

    /**
     * Set key.
     */
    public function setKey(?string $key): static;

    /**
     * Set defaultLocale.
     */
    public function setDefaultLocale(string $defaultLocale): static;

    /**
     * Get defaultLocale.
     */
    public function getDefaultLocale(): string;

    /**
     * Get id.
     */
    public function getId(): ?int;

    /**
     * Add translations.
     */
    public function addTranslation(CategoryTranslationInterface $translations): static;

    /**
     * Remove translations.
     */
    public function removeTranslation(CategoryTranslationInterface $translations): static;

    /**
     * Get translations.
     *
     * @return Collection<int, CategoryTranslationInterface>
     */
    public function getTranslations(): Collection;

    /**
     * Get single translation by locale or null if does not exist.
     */
    public function findTranslationByLocale(?string $locale): ?CategoryTranslationInterface;

    /**
     * Add children.
     */
    public function addChild(self $child): static;

    /**
     * Remove children.
     */
    public function removeChild(self $child): static;

    /**
     * Get children.
     *
     * @return Collection<int, CategoryInterface>
     */
    public function getChildren(): Collection;

    /**
     * Set parent.
     */
    public function setParent(?self $parent = null): static;

    /**
     * Get parent.
     */
    public function getParent(): ?CategoryInterface;
}
