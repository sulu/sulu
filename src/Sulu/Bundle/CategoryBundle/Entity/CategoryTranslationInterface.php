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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Interface for the extensible CategoryTranslation entity.
 */
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
     * Get description.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Set description.
     *
     * @param $description
     *
     * @return CategoryTranslationInterface
     */
    public function setDescription($description);

    /**
     * Get description.
     *
     * @return Media[]
     */
    public function getMedias();

    /**
     * Set images.
     *
     * @param Media[] $images
     *
     * @return CategoryTranslationInterface
     */
    public function setMedias($images);

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
     * @param KeywordInterface $keyword
     *
     * @return CategoryInterface
     */
    public function addKeyword(KeywordInterface $keyword);

    /**
     * Remove keyword.
     *
     * @param KeywordInterface $keyword
     */
    public function removeKeyword(KeywordInterface $keyword);

    /**
     * Get keywords.
     *
     * @return Collection
     */
    public function getKeywords();

    /**
     * Returns true if given keyword already linked with the category.
     *
     * @param KeywordInterface $keyword
     *
     * @return bool
     */
    public function hasKeyword(KeywordInterface $keyword);

    /**
     * @param UserInterface $creator
     */
    public function setCreator($creator);

    /**
     * @param UserInterface $changer
     */
    public function setChanger($changer);

    /**
     * @param \DateTime $created
     */
    public function setCreated($created);

    /**
     * @param \DateTime $changed
     */
    public function setChanged($changed);
}
