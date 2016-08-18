<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

interface CategoryTranslationInterface extends AuditableInterface
{
    /**
     * Set translation.
     *
     * @param string $translation
     *
     * @return CategoryTranslationInterface
     */
    public function setTranslation($translation);
    /**
     * Get translation.
     *
     * @return string
     */
    public function getTranslation();

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return CategoryTranslationInterface
     */
    public function setLocale($locale);

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set category.
     *
     * @param CategoryInterface $category
     *
     * @return CategoryTranslationInterface
     */
    public function setCategory(CategoryInterface $category);

    /**
     * Get category.
     *
     * @return CategoryInterface
     */
    public function getCategory();

    /**
     * Add keyword.
     *
     * @param Keyword $keyword
     *
     * @return CategoryInterface
     */
    public function addKeyword(Keyword $keyword);

    /**
     * Remove keyword.
     *
     * @param Keyword $keyword
     */
    public function removeKeyword(Keyword $keyword);

    /**
     * Get keywords.
     *
     * @return Collection
     */
    public function getKeywords();

    /**
     * Returns true if given keyword already linked with the category.
     *
     * @param Keyword $keyword
     *
     * @return bool
     */
    public function hasKeyword(Keyword $keyword);

    /**
     * {@inheritdoc}
     */
    public function getCreator();

    /**
     * @param UserInterface $creator
     */
    public function setCreator($creator);

    /**
     * {@inheritdoc}
     */
    public function getChanger();

    /**
     * @param UserInterface $changer
     */
    public function setChanger($changer);

    /**
     * {@inheritdoc}
     */
    public function getCreated();

    /**
     * @param \DateTime $created
     */
    public function setCreated($created);

    /**
     * {@inheritdoc}
     */
    public function getChanged();

    /**
     * @param \DateTime $changed
     */
    public function setChanged($changed);
}
