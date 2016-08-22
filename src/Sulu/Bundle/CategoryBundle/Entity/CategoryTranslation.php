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

/**
 * CategoryTranslation.
 */
class CategoryTranslation extends BaseCategoryTranslation
{
    /**
     * @var Collection
     */
    private $keywords;

    public function __construct()
    {
        $this->keywords = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function addKeyword(KeywordInterface $keyword)
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeKeyword(KeywordInterface $keyword)
    {
        $this->keywords->removeElement($keyword);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * {@inheritdoc}
     */
    public function hasKeyword(KeywordInterface $keyword)
    {
        return $this->getKeywords()->exists(
            function ($key, KeywordInterface $element) use ($keyword) {
                return $element->equals($keyword);
            }
        );
    }
}
