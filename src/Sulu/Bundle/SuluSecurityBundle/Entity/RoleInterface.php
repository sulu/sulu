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
 * Defines the interface for a role
 * @package Sulu\Bundle\SecurityBundle\Entity
 */
interface RoleInterface
{
    /**
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name);

    /**
     * Get name
     *
     * @return string
     */
    public function getName();

    /**
     * Set system
     *
     * @param string $system
     * @return Role
     */
    public function setSystem($system);

    /**
     * Get system
     *
     * @return string
     */
    public function getSystem();

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Role
     */
    public function setCreated($created);

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Set changed
     *
     * @param \DateTime $changed
     * @return Role
     */
    public function setChanged($changed);

    /**
     * Get changed
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Set creator
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $creator
     * @return Role
     */
    public function setCreator(\Sulu\Bundle\SecurityBundle\Entity\User $creator = null);

    /**
     * Get creator
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User
     */
    public function getCreator();

    /**
     * Set changer
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\User $changer
     * @return Role
     */
    public function setChanger(\Sulu\Bundle\SecurityBundle\Entity\User $changer = null);

    /**
     * Get changer
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\User
     */
    public function getChanger();

    /**
     * Add permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     * @return Role
     */
    public function addPermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions);

    /**
     * Remove permissions
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     */
    public function removePermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions);

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions();

    /**
     * Add userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     * @return UserRole
     */
    public function addUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles);

    /**
     * Remove userRoles
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     */
    public function removeUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles);

    /**
     * Get userRoles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles();

    /**
     * Add groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     * @return Role
     */
    public function addGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups);

    /**
     * Remove groups
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     */
    public function removeGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups);

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups();

    /**
     * Set securityType
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType
     * @return Role
     */
    public function setSecurityType(\Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType = null);

    /**
     * Get securityType
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\SecurityType
     */
    public function getSecurityType();
}
