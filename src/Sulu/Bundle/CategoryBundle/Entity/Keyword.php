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

/**
 * The keywords can describe a category with different words.
 */
class Keyword implements AuditableInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $keyword;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var Collection
     */
    private $categoryTranslations;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->categoryTranslations = new ArrayCollection();
    }

    /**
     * Set locale.
     *
     * @param string $locale
     *
     * @return Keyword
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set keyword.
     *
     * @param string $keyword
     *
     * @return Keyword
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * Get keyword.
     *
     * @return string
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add category-translation.
     *
     * @param CategoryTranslation $categoryTranslation
     *
     * @return Keyword
     */
    public function addCategoryTranslation(CategoryTranslation $categoryTranslation)
    {
        $this->categoryTranslations[] = $categoryTranslation;

        return $this;
    }

    /**
     * Remove category-translation.
     *
     * @param CategoryTranslation $categoryTranslation
     */
    public function removeCategoryTranslation(CategoryTranslation $categoryTranslation)
    {
        $this->categoryTranslations->removeElement($categoryTranslation);
    }

    /**
     * Get categories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCategoryTranslations()
    {
        return $this->categoryTranslations;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param UserInterface $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param UserInterface $changer
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param \DateTime $changed
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * @return bool
     */
    public function isReferencedMultiple()
    {
        return $this->getCategoryTranslations()->count() > 1;
    }

    /**
     * @return bool
     */
    public function isReferenced()
    {
        return $this->getCategoryTranslations()->count() > 0;
    }

    /**
     * @return int
     */
    public function getCategoryTranslationCount()
    {
        return $this->getCategoryTranslations()->count();
    }

    /**
     * @param Keyword $keyword
     *
     * @return bool
     */
    public function equals(Keyword $keyword)
    {
        return $keyword->getKeyword() === $this->getKeyword()
            && $keyword->getLocale() === $this->getLocale();
    }
}
