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
     * @var Collection<int, CategoryTranslationInterface>
     */
    protected $categoryTranslations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->categoryTranslations = new ArrayCollection();
    }

    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getKeyword()
    {
        return $this->keyword;
    }

    public function getId()
    {
        return $this->id;
    }

    public function equals(KeywordInterface $keyword)
    {
        return $keyword->getKeyword() === $this->getKeyword()
        && $keyword->getLocale() === $this->getLocale();
    }

    public function addCategoryTranslation(CategoryTranslationInterface $categoryTranslation)
    {
        $this->categoryTranslations[] = $categoryTranslation;

        return $this;
    }

    public function removeCategoryTranslation(CategoryTranslationInterface $categoryTranslation)
    {
        $this->categoryTranslations->removeElement($categoryTranslation);
    }

    public function getCategoryTranslations()
    {
        return $this->categoryTranslations;
    }

    public function isReferencedMultiple()
    {
        return $this->getCategoryTranslations()->count() > 1;
    }

    public function isReferenced()
    {
        return $this->getCategoryTranslations()->count() > 0;
    }

    public function getCategoryTranslationCount()
    {
        return $this->getCategoryTranslations()->count();
    }
}
