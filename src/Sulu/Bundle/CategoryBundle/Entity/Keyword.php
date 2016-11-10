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
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * The keywords can describe a category with different words.
 */
class Keyword implements KeywordInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $keyword;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var UserInterface
     */
    protected $changer;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var Collection
     */
    protected $categoryTranslations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->categoryTranslations = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyword()
    {
        return $this->keyword;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(KeywordInterface $keyword)
    {
        return $keyword->getKeyword() === $this->getKeyword()
        && $keyword->getLocale() === $this->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function addCategoryTranslation(CategoryTranslationInterface $categoryTranslation)
    {
        $this->categoryTranslations[] = $categoryTranslation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeCategoryTranslation(CategoryTranslationInterface $categoryTranslation)
    {
        $this->categoryTranslations->removeElement($categoryTranslation);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryTranslations()
    {
        return $this->categoryTranslations;
    }

    /**
     * {@inheritdoc}
     */
    public function isReferencedMultiple()
    {
        return $this->getCategoryTranslations()->count() > 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isReferenced()
    {
        return $this->getCategoryTranslations()->count() > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryTranslationCount()
    {
        return $this->getCategoryTranslations()->count();
    }
}
