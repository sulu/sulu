<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;

/**
 * Group
 */
class Group extends ApiEntity
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
     * @var string
     */
    private $name;

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
    private $children;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userGroups;

    /**
     * @var \Sulu\Bundle\SecurityBundle\Entity\Group
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $roles;

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
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set lft
     *
     * @param integer $lft
     * @return Group
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
     * @return Group
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
     * @return Group
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
     * Set name
     *
     * @param string $name
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Group
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
     * @return Group
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
     * Add children
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $children
     * @return Group
     */
    public function addChildren(\Sulu\Bundle\SecurityBundle\Entity\Group $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $children
     */
    public function removeChildren(\Sulu\Bundle\SecurityBundle\Entity\Group $children)
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
     * Add userGroups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups
     * @return Group
     */
    public function addUserGroup(\Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups
     */
    public function removeUserGroup(\Sulu\Bundle\SecurityBundle\Entity\UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Set parent
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $parent
     * @return Group
     */
    public function setParent(\Sulu\Bundle\SecurityBundle\Entity\Group $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add roles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\RoleInterface $roles
     * @return Group
     */
    public function addRole(\Sulu\Bundle\SecurityBundle\Entity\RoleInterface $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\RoleInterface $roles
     */
    public function removeRole(\Sulu\Bundle\SecurityBundle\Entity\RoleInterface $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $changer
     * @return Group
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
     * @return Group
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
}
