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

/**
 * Interface for the extensible CategoryMeta entity.
 */
interface CategoryMetaInterface
{
    /**
     * Set key.
     */
    public function setKey(string $key): static;

    /**
     * Get key.
     */
    public function getKey(): string;

    /**
     * Set value.
     */
    public function setValue(string $value): static;

    /**
     * Get value.
     */
    public function getValue(): string;

    /**
     * Set locale.
     */
    public function setLocale(?string $locale): static;

    /**
     * Get locale.
     */
    public function getLocale(): ?string;

    /**
     * Get id.
     */
    public function getId(): ?int;

    /**
     * Set id.
     */
    public function setId(?int $id): static;

    /**
     * Set category.
     */
    public function setCategory(CategoryInterface $category): static;

    /**
     * Get category.
     */
    public function getCategory(): CategoryInterface;
}
