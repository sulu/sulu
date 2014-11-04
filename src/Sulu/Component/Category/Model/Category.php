<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Category\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sulu\Component\Security\UserInterface;

class Category implements CategoryInterface
{

    /**
     * @var integer|string
     */
    private $id;

    /**
     * @var string
     */
    private $key;

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
     * @var CategoryInterface
     */
    private $parent;

    /**
     * @var integer
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
     * @var UserInterface
     */
    private $creator;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     */
    public function addMeta(CategoryMetaInterface $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeMeta(CategoryMetaInterface $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * {@inheritDoc}
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * {@inheritDoc}
     */
    public function addTranslation(CategoryTranslationInterface $translation)
    {
        $this->translations[] = $translation;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeTranslation(CategoryTranslationInterface $translation)
    {
        $this->translations->removeElement($translation);
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * {@inheritDoc}
     */
    public function addChildren(CategoryInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function removeChildren(CategoryInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * {@inheritDoc}
     */
    public function setParent(CategoryInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritDoc}
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * {@inheritDoc}
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritDoc}
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * {@inheritDoc}
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * {@inheritDoc}
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }
}
