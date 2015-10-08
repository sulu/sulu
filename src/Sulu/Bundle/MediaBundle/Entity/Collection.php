<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use JMS\Serializer\Annotation\Exclude;

/**
 * Collection.
 */
class Collection extends BaseCollection
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $meta;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $media;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    private $parent;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionMeta
     */
    private $defaultMeta;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param \Doctrine\Common\Collections\ArrayCollection $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * Set parent.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent
     *
     * @return CollectionInterface
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add meta.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta
     *
     * @return Collection
     */
    public function addMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta
     */
    public function removeMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add media.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     *
     * @return Collection
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Remove media.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media.
     *
     * @return \Doctrine\Common\Collections\CollectionInterface
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add children.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children
     *
     * @return Collection
     */
    public function addChildren(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children
     */
    public function removeChildren(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Set defaultMeta.
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $defaultMeta
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
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionMeta
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
