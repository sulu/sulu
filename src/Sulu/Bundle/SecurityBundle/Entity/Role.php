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

use JMS\Serializer\Annotation\Exclude;

/**
 * Role.
 */
class Role extends BaseRole
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $permissions;

    /**
     * @var \Doctrine\Common\Collections\Collection
     * @Exclude
     */
    private $userRoles;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $groups;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->permissions = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userRoles = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add permissions.
     *
     * @param Permission $permissions
     *
     * @return Role
     */
    public function addPermission(Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions.
     *
     * @param Permission $permissions
     */
    public function removePermission(Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Add userRoles.
     *
     * @param UserRole $userRoles
     *
     * @return Role
     */
    public function addUserRole(UserRole $userRoles)
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    /**
     * Remove userRoles.
     *
     * @param UserRole $userRoles
     */
    public function removeUserRole(UserRole $userRoles)
    {
        $this->userRoles->removeElement($userRoles);
    }

    /**
     * Get userRoles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles()
    {
        return $this->userRoles;
    }

    /**
     * Add groups.
     *
     * @param Group $groups
     *
     * @return Role
     */
    public function addGroup(Group $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups.
     *
     * @param Group $groups
     */
    public function removeGroup(Group $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }
}
