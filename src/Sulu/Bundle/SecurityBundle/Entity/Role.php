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

/**
 * Role
 */
class Role extends BaseRole
{

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $userRoles;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     * @return Role
     */
    public function addPermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     */
    public function removePermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     * @return Role
     */
    public function addUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     */
    public function removeUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * Add groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     * @return Role
     */
    public function addGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     */
    public function removeGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
