<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Collection.
 */
class Collection implements CollectionInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $style;

    /**
     * @var int
     * @Exclude
     */
    protected $lft;

    /**
     * @var int
     * @Exclude
     */
    protected $rgt;

    /**
     * @var int
     * @Exclude
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
     * @var CollectionType
     */
    protected $type;

    /**
     * @var UserInterface
     * @Exclude
     */
    protected $changer;

    /**
     * @var UserInterface
     * @Exclude
     */
    protected $creator;

    /**
     * @var string
     */
    private $key;

    /**
     * @var DoctrineCollection|CollectionMeta[]
     */
    private $meta;

    /**
     * @var DoctrineCollection|MediaInterface[]
     * @Exclude
     */
    private $media;

    /**
     * @var DoctrineCollection|CollectionInterface[]
     */
    private $children;

    /**
     * @var CollectionInterface
     */
    private $parent;

    /**
     * @var CollectionMeta
     */
    private $defaultMeta;

    public function __construct()
    {
        $this->meta = new ArrayCollection();
        $this->media = new ArrayCollection();
        $this->children = new ArrayCollection();
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
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return CollectionInterface
     */
    public function setChanger(UserInterface $changer = null)
    {
        $this->changer = $changer;

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

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return CollectionInterface
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
     * Set style.
     *
     * @param string $style
     *
     * @return CollectionInterface
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Get style.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return CollectionInterface
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
     * @return CollectionInterface
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
     * @return CollectionInterface
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
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Set type.
     *
     * @param CollectionType $type
     *
     * @return CollectionInterface
     */
    public function setType(CollectionType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return CollectionType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get key.
     *
     * @param string $key
     *
     * @return CollectionInterface
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return DoctrineCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param DoctrineCollection $children
     */
    public function setChildren(DoctrineCollection $children)
    {
        $this->children = $children;
    }

    /**
     * Set parent.
     *
     * @param CollectionInterface $parent
     *
     * @return CollectionInterface
     */
    public function setParent(CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return CollectionInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add meta.
     *
     * @param CollectionMeta $meta
     *
     * @return Collection
     */
    public function addMeta(CollectionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param CollectionMeta $meta
     */
    public function removeMeta(CollectionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return DoctrineCollection|CollectionMeta[]
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add media.
     *
     * @param MediaInterface $media
     *
     * @return Collection
     */
    public function addMedia(MediaInterface $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Remove media.
     *
     * @param MediaInterface $media
     */
    public function removeMedia(MediaInterface $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media.
     *
     * @return DoctrineCollection|MediaInterface[]
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add children.
     *
     * @param CollectionInterface $children
     *
     * @return Collection
     */
    public function addChildren(CollectionInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param CollectionInterface $children
     */
    public function removeChildren(CollectionInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Set defaultMeta.
     *
     * @param CollectionMeta $defaultMeta
     *
     * @return Collection
     */
    public function setDefaultMeta(CollectionMeta $defaultMeta = null)
    {
        $this->defaultMeta = $defaultMeta;

        return $this;
    }

    /**
     * Get defaultMeta.
     *
     * @return CollectionMeta
     */
    public function getDefaultMeta()
    {
        return $this->defaultMeta;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.media.collections';
    }
}
