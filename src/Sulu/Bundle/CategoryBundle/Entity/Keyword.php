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
class Keyword extends BaseKeyword
{
    /**
     * @var Collection
     */
    private $categoryTranslations;

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
