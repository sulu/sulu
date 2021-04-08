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
use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * CategoryTranslation.
 */
class CategoryTranslation implements CategoryTranslationInterface
{
    /**
     * @var string
     */
    protected $translation;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var Media[]
     */
    protected $medias;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var CategoryInterface
     */
    protected $category;

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
    protected $keywords;

    public function __construct()
    {
        $this->keywords = new ArrayCollection();
        $this->medias = new ArrayCollection();
    }

    public function setTranslation($translation)
    {
        $this->translation = $translation;

        return $this;
    }

    public function getTranslation()
    {
        return $this->translation;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getMedias()
    {
        return $this->medias;
    }

    public function setMedias($medias)
    {
        $this->medias = $medias;
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

    public function getId()
    {
        return $this->id;
    }

    public function setCategory(CategoryInterface $category)
    {
        $this->category = $category;

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getCreator()
    {
        return $this->creator;
    }

    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getChanger()
    {
        return $this->changer;
    }

    public function setChanger($changer)
    {
        $this->changer = $changer;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated($created)
    {
        $this->created = $created;
    }

    public function getChanged()
    {
        return $this->changed;
    }

    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    public function addKeyword(KeywordInterface $keyword)
    {
        $this->keywords[] = $keyword;

        return $this;
    }

    public function removeKeyword(KeywordInterface $keyword)
    {
        $this->keywords->removeElement($keyword);
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function hasKeyword(KeywordInterface $keyword)
    {
        return $this->getKeywords()->exists(
            function($key, KeywordInterface $element) use ($keyword) {
                return $element->equals($keyword);
            }
        );
    }
}
