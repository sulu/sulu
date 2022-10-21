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
     * @return CategoryTranslationInterface
     */
    public function setDescription($description);

    /**
     * Get description.
     *
     * @return MediaInterface[]
     */
    public function getMedias();

    /**
     * Set images.
     *
     * @param MediaInterface[] $images
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
     * @return CategoryInterface
     */
    public function addKeyword(KeywordInterface $keyword);

    /**
     * Remove keyword.
     */
    public function removeKeyword(KeywordInterface $keyword);

    /**
     * Get keywords.
     *
     * @return Collection|KeywordInterface[]
     */
    public function getKeywords();

    /**
     * Returns true if given keyword already linked with the category.
     *
     * @return bool
     */
    public function hasKeyword(KeywordInterface $keyword);

    /**
     * @param UserInterface|null $creator
     */
    public function setCreator($creator);

    /**
     * @param UserInterface|null $changer
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
