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

/**
 * Collection
 */
class Collection
{
    /**
     * @var string
     */
    private $style;

    /**
     * @var integer
     */
    private $lft;

    /**
     * @var integer
     */
    private $rgt;

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
     * @var integer
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $metas;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $medias;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\CollectionType
     */
    private $collectionType;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $changer;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\User
     */
    private $creator;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->metas = new \Doctrine\Common\Collections\ArrayCollection();
        $this->medias = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set style
     *
     * @param string $style
     * @return Collection
     */
    public function setStyle($style)
    {
        $this->style = $style;
    
        return $this;
    }

    /**
     * Get style
     *
     * @return string 
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Collection
     */
    public function setLft($lft)
    {
        $this->lft = $lft;
    
        return $this;
    }

    /**
     * Get lft
     *
     * @return integer 
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt
     *
     * @param integer $rgt
     * @return Collection
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;
    
        return $this;
    }

    /**
     * Get rgt
     *
     * @return integer 
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set depth
     *
     * @param integer $depth
     * @return Collection
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    
        return $this;
    }

    /**
     * Get depth
     *
     * @return integer 
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Collection
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Collection
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    
        return $this;
    }

    /**
     * Get changed
     *
     * @return \DateTime 
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add metas
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $metas
     * @return Collection
     */
    public function addMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $metas)
    {
        $this->metas[] = $metas;
    
        return $this;
    }

    /**
     * Remove metas
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionMeta $metas
     */
    public function removeMeta(\Sulu\Bundle\MediaBundle\Entity\CollectionMeta $metas)
    {
        $this->metas->removeElement($metas);
    }

    /**
     * Get metas
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMetas()
    {
        return $this->metas;
    }

    /**
     * Add medias
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     * @return Collection
     */
    public function addMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias[] = $medias;
    
        return $this;
    }

    /**
     * Remove medias
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Media $medias
     */
    public function removeMedia(\Sulu\Bundle\MediaBundle\Entity\Media $medias)
    {
        $this->medias->removeElement($medias);
    }

    /**
     * Get medias
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMedias()
    {
        return $this->medias;
    }

    /**
     * Set collectionType
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\CollectionType $collectionType
     * @return Collection
     */
    public function setCollectionType(\Sulu\Bundle\MediaBundle\Entity\CollectionType $collectionType)
    {
        $this->collectionType = $collectionType;
    
        return $this;
    }

    /**
     * Get collectionType
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\CollectionType 
     */
    public function getCollectionType()
    {
        return $this->collectionType;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $changer
     * @return Collection
     */
    public function setChanger(\Sulu\Bundle\SecurityBundle\Entity\User $changer = null)
    {
        $this->changer = $changer;
    
        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User 
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $creator
     * @return Collection
     */
    public function setCreator(\Sulu\Bundle\SecurityBundle\Entity\User $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User 
     */
    public function getCreator()
    {
        return $this->creator;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Sulu\Bundle\MediaBundle\Entity\Collection
     */
    private $parent;


    /**
     * Add children
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Collection $children
     * @return Collection
     */
    public function addChildren(\Sulu\Bundle\MediaBundle\Entity\Collection $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Collection $children
     */
    public function removeChildren(\Sulu\Bundle\MediaBundle\Entity\Collection $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\MediaBundle\Entity\Collection $parent
     * @return Collection
     */
    public function setParent(\Sulu\Bundle\MediaBundle\Entity\Collection $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\MediaBundle\Entity\Collection 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * generateColor
     * @return string
     */
    static public function generateColor()
    {
        $colorRange = 192 - 64;
        $colorFactor = $colorRange / 256;
        $colorOffset = 64;

        $base_hash = substr(md5(rand()), 0, 6);
        $baseRed = hexdec(substr($base_hash,0,2));
        $baseGreen = hexdec(substr($base_hash,2,2));
        $baseBlue = hexdec(substr($base_hash,4,2));

        $red = floor((floor($baseRed * $colorFactor) + $colorOffset) / 16) * 16;
        $green = floor((floor($baseGreen * $colorFactor) + $colorOffset) / 16) * 16;
        $blue = floor((floor($baseBlue * $colorFactor) + $colorOffset) / 16) * 16;

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }
}