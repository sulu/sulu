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
     * @var string|null
     */
    protected $description;

    /**
     * @var Collection<int, CategoryTranslationMedia>
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
     * @var UserInterface|null
     */
    protected $creator;

    /**
     * @var UserInterface|null
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
     * @var Collection<int, KeywordInterface>
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

    /**
     * @return string|null
     */
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
        $medias = [];

        foreach ($this->medias as $media) {
            $medias[] = $media->getMedia();
        }

        return $medias;
    }

    public function setMedias($medias)
    {
        $position = 0;
        foreach ($this->medias as $media) {
            $mediaEntity = $medias[$position] ?? null;
            ++$position;

            if (!$mediaEntity) {
                $this->medias->removeElement($media);

                continue;
            }

            $media->setMedia($mediaEntity);
            $media->setPosition($position);
        }

        for (; $position < \count($medias); ++$position) {
            $media = new CategoryTranslationMedia($this, $medias[$position], $position + 1);
            $this->medias->add($media);
        }
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
        return $this->keywords->exists(
            function($key, KeywordInterface $element) use ($keyword) {
                return $element->equals($keyword);
            }
        );
    }
}
