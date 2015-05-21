<?php

namespace Sulu\Bundle\CategoryBundle\Entity;

use Sulu\Component\Persistence\Model\AuditableInterface;

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
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $creator;

    /**
     * @var \Sulu\Component\Security\Authentication\UserInterface
     */
    private $changer;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->meta = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta
     *
     * @return Category
     */
    public function addMeta(\Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta)
    {
        $this->meta[] = $meta;

        return $this;
    }

    /**
     * Remove meta.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta
     */
    public function removeMeta(\Sulu\Bundle\CategoryBundle\Entity\CategoryMeta $meta)
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
     * Add translations.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations
     *
     * @return Category
     */
    public function addTranslation(\Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations[] = $translations;

        return $this;
    }

    /**
     * Remove translations.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations
     */
    public function removeTranslation(\Sulu\Bundle\CategoryBundle\Entity\CategoryTranslation $translations)
    {
        $this->translations->removeElement($translations);
    }

    /**
     * Get translations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * Add children.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $children
     *
     * @return Category
     */
    public function addChildren(\Sulu\Bundle\CategoryBundle\Entity\Category $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $children
     */
    public function removeChildren(\Sulu\Bundle\CategoryBundle\Entity\Category $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $parent
     *
     * @return Category
     */
    public function setParent(\Sulu\Bundle\CategoryBundle\Entity\Category $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \Sulu\Bundle\CategoryBundle\Entity\Category
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set creator.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $creator
     *
     * @return Category
     */
    public function setCreator(\Sulu\Component\Security\Authentication\UserInterface $creator = null)
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Get creator.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Set changer.
     *
     * @param \Sulu\Component\Security\Authentication\UserInterface $changer
     *
     * @return Category
     */
    public function setChanger(\Sulu\Component\Security\Authentication\UserInterface $changer = null)
    {
        $this->changer = $changer;

        return $this;
    }

    /**
     * Get changer.
     *
     * @return \Sulu\Component\Security\Authentication\UserInterface
     */
    public function getChanger()
    {
        return $this->changer;
    }
}
