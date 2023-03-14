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
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * The keywords can describe a category with different words.
 */
interface KeywordInterface extends AuditableInterface
{
    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return KeywordInterface
     */
    public function setLocale($locale);

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Set keyword.
     *
     * @param string $keyword
     *
     * @return KeywordInterface
     */
    public function setKeyword($keyword);

    /**
     * Get keyword.
     *
     * @return string
     */
    public function getKeyword();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Add category-translation.
     *
     * @return KeywordInterface
     */
    public function addCategoryTranslation(CategoryTranslationInterface $categoryTranslation);

    /**
     * Remove category-translation.
     *
     * @return void
     */
    public function removeCategoryTranslation(CategoryTranslationInterface $categoryTranslation);

    /**
     * Get categories.
     *
     * @return Collection<int, CategoryTranslationInterface>
     */
    public function getCategoryTranslations();

    /**
     * @param UserInterface|null $creator
     *
     * @return void
     */
    public function setCreator($creator);

    /**
     * @param UserInterface|null $changer
     *
     * @return void
     */
    public function setChanger($changer);

    /**
     * @param \DateTime $created
     *
     * @return void
     */
    public function setCreated($created);

    /**
     * @param \DateTime $changed
     *
     * @return void
     */
    public function setChanged($changed);

    /**
     * @return bool
     */
    public function isReferencedMultiple();

    /**
     * @return bool
     */
    public function isReferenced();

    /**
     * @return int
     */
    public function getCategoryTranslationCount();

    /**
     * @return bool
     */
    public function equals(self $keyword);
}
