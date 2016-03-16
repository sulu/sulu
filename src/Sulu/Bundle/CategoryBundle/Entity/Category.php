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
 * Category.
 */
class Category implements AuditableInterface
{
    /**
     * @var int
     */
    private $lft;

    /**
     * @var int
     */
    private $rgt;

    /**
     * @var int
     */
    private $depth;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $changed;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $defaultLocale;

    /**
     * @var Collection
     */
    private $meta;

    /**
     * @var Collection
     */
    private $translations;

    /**
     * @var Collection
     */
    private $children;

    /**
     * @var Category
     */
    private $parent;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * @var UserInterface
     */
    private $changer;

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
     * Set id.
     *
     * @param int $id
     *
     * @return Category
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Category
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int $rgt
     *
     * @return Category
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth.
     *
     * @param int $depth
     *
     * @return Category
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * Get depth.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return Category
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set defaultLocale.
     *
     * @param string $defaultLocale
     *
     * @return Category
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Get defaultLocale.
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
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
     * Add meta.
     *
     * @param CategoryMeta $meta
     *
     * @return Category
     */
    public function addMeta(CategoryMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param CategoryMeta $meta
     */
    public function removeMeta(CategoryMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return Collection
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add translations.
     *
     * @param CategoryTranslation $translations
     *
     * @return Category
     */
    public function addTranslation(CategoryTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param CategoryTranslation $translations
     */
    public function removeTranslation(CategoryTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Get single meta by locale.
     *
     * @param $locale
     *
     * @return CategoryTranslation
     */
    public function findTranslationByLocale($locale)
    {
        return $this->translations->filter(
            function (CategoryTranslation $translation) use ($locale) {
                return $translation->getLocale() === $locale;
            }
        )->first();
    }

    /**
     * {@see Category::addChild}.
     *
     * @deprecated use Category::addChild instead
     */
    public function addChildren(Category $child)
    {
        $this->addChild($child);
    }

    /**
     * Add children.
     *
     * @param Category $child
     *
     * @return Category
     */
    public function addChild(Category $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * {@see Category::removeChild}.
     *
     * @deprecated use Category::addChild instead
     */
    public function removeChildren(Category $child)
    {
        $this->removeChild($child);
    }

    /**
     * Remove children.
     *
     * @param Category $child
     */
    public function removeChild(Category $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param Category $parent
     *
     * @return Category
     */
    public function setParent(Category $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return Category
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return Category
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Set changed.
     *
     * @param \DateTime $changed
     *
     * @return Category
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }
}
