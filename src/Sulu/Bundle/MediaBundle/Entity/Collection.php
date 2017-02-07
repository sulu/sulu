<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use JMS\Serializer\Annotation\Exclude;

/**
 * Collection.
 */
class Collection extends BaseCollection
{
    /**
     * @var DoctrineCollection
     */
    private $meta;

    /**
     * @var DoctrineCollection
     * @Exclude
     */
    private $media;

    /**
     * @var DoctrineCollection
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
     * @return DoctrineCollection
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
     * @return DoctrineCollection
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
