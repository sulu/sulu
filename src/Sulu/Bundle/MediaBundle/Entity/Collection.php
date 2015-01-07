<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Exclude;

/**
 * Collection
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
     * Constructor
     */
    public function __construct()
    {
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->media = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent
     * @return CollectionInterface
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionInterface
     */
    public function getParent()
    {
        return $this->parent;
    }


    /**
     * Add meta
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta
     * @return Collection
     */
    public function addMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta
     */
    public function removeMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $meta)
    {
        $this->meta->removeElement($meta);
    }

    /**
     * Get meta
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * Add media
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     * @return Collection
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media[] = $media;

        return $this;
    }

    /**
     * Remove media
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $media
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $media)
    {
        $this->media->removeElement($media);
    }

    /**
     * Get media
     *
     * @return \Doctrine\Common\Collections\CollectionInterface
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add children
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children
     * @return Collection
     */
    public function addChildren(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children
     */
    public function removeChildren(\Sulu\Bundle\MediaBundle\Entity\CollectionInterface $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\CollectionInterface
     */
    public function getChildren()
    {
        return $this->children;
    }
}
