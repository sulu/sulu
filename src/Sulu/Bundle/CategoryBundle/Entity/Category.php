<?php

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Category
 */
class Category
{
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
     * @var string
     */
    private $key;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $meta;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $translations;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Sulu\Bundle\CategoryBundle\Entity\Category
     */
    private $parent;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $creator;

    /**
     * @var \Sulu\Component\Security\UserInterface
     */
    private $changer;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set lft
     *
     * @param integer $lft
     * @return Category
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
     * @return Category
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
     * @return Category
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
     * @return Category
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
     * @return Category
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    
        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set key
     *
     * @param string $key
     * @return Category
     */
    public function setKey($key)
    {
        $this->key = $key;

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
     * Add meta
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta
     * @return Category
     */
    public function addMeta(\Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta)
    {
        $this->meta[] = $meta;
    
        return $this;
    }

    /**
     * Remove meta
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta
     */
    public function removeMeta(\Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta)
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
     * Add translations
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations
     * @return Category
     */
    public function addTranslation(\Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations[] = $translations;
    
        return $this;
    }

    /**
     * Remove translations
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add children
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $children
     * @return Category
     */
    public function addChildren(\Sulu\Bundle\CategoryBundle\Entity\Category $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $children
     */
    public function removeChildren(\Sulu\Bundle\CategoryBundle\Entity\Category $children)
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
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $parent
     * @return Category
     */
    public function setParent(\Sulu\Bundle\CategoryBundle\Entity\Category $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\CategoryBundle\Entity\Category 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set creator
     *
     * @param \Sulu\Component\Security\UserInterface $creator
     * @return Category
     */
    public function setCreator(\Sulu\Component\Security\UserInterface $creator = null)
    {
        $this->creator = $creator;
    
        return $this;
    }

    /**
     * Get creator
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Component\Security\UserInterface $changer
     * @return Category
     */
    public function setChanger(\Sulu\Component\Security\UserInterface $changer = null)
    {
        $this->changer = $changer;
    
        return $this;
    }

    /**
     * Get changer
     *
     * @return \Sulu\Component\Security\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }
}
