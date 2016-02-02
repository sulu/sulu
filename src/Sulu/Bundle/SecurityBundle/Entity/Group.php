<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Sulu\Bundle\CoreBundle\Entity\ApiEntity;
use Sulu\Component\Persistence\Model\AuditableInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Group.
 */
class Group extends ApiEntity implements AuditableInterface
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
     * @var int
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
     * @var Group
     */
    private $parent;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $roles;

    /**
     * @var UserInterface
     */
    private $changer;

    /**
     * @var UserInterface
     */
    private $creator;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return Group
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
     * @return Group
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
     * @return Group
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
     * Set name.
     *
     * @param string $name
     *
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Add children.
     *
     * @param Group $children
     *
     * @return Group
     */
    public function addChildren(Group $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children.
     *
     * @param Group $children
     */
    public function removeChildren(Group $children)
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
     * Add userGroups.
     *
     * @param UserGroup $userGroups
     *
     * @return Group
     */
    public function addUserGroup(UserGroup $userGroups)
    {
        $this->userGroups[] = $userGroups;

        return $this;
    }

    /**
     * Remove userGroups.
     *
     * @param UserGroup $userGroups
     */
    public function removeUserGroup(UserGroup $userGroups)
    {
        $this->userGroups->removeElement($userGroups);
    }

    /**
     * Get userGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserGroups()
    {
        return $this->userGroups;
    }

    /**
     * Set parent.
     *
     * @param Group $parent
     *
     * @return Group
     */
    public function setParent(Group $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add roles.
     *
     * @param RoleInterface $roles
     *
     * @return Group
     */
    public function addRole(RoleInterface $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles.
     *
     * @param RoleInterface $roles
     */
    public function removeRole(RoleInterface $roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return Group
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
     * @return Group
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
}
