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
abstract class BaseCategory implements CategoryInterface
{
    /**
     * @var int
     */
    protected $lft;

    /**
     * @var int
     */
    protected $rgt;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var \DateTime
     */
    protected $created;

    /**
     * @var \DateTime
     */
    protected $changed;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $defaultLocale;

    /**
     * @var CategoryInterface
     */
    protected $parent;

    /**
     * @var UserInterface
     */
    protected $creator;

    /**
     * @var UserInterface
     */
    protected $changer;

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * {@inheritdoc}
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * {@inheritdoc}
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDepth()
    {
        return $this->depth;
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
    public function getKey()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function setParent(CategoryInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreator(UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
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
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setChanged(\DateTime $changed)
    {
        $this->changed = $changed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChanger()
    {
        return $this->changer;
    }
}
