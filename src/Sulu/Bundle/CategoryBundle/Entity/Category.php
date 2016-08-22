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
 * Category.
 */
class Category extends BaseCategory
{
    /**
     * @var Collection
     */
    protected $meta;

    /**
     * @var Collection
     */
    protected $translations;

    /**
     * @var Collection
     */
    protected $children;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function addMeta(CategoryMetaInterface $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeMeta(CategoryMetaInterface $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * {@inheritdoc}
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * {@inheritdoc}
     */
    public function addTranslation(CategoryTranslationInterface $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeTranslation(CategoryTranslationInterface $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslationByLocale($locale)
    {
        return $this->translations->filter(
            function (CategoryTranslationInterface $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first();
    }

    /**
     * {@inheritdoc}
     */
    public function addChildren(CategoryInterface $child)
    {
        $this->addChild($child);
    }

    /**
     * {@inheritdoc}
     */
    public function addChild(CategoryInterface $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function removeChildren(CategoryInterface $child)
    {
        $this->removeChild($child);
    }

    /**
     * {@inheritdoc}
     */
    public function removeChild(CategoryInterface $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return $this->children;
    }
}
