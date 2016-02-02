<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Defines the interface for a role.
 */
interface RoleInterface extends AuditableInterface, SecurityIdentityInterface
{
    /**
     * Set name.
     *
     * @param string $name
     *
     * @return RoleInterface
     */
    public function setName($name);

    /**
     * Get name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set system.
     *
     * @param string $system
     *
     * @return RoleInterface
     */
    public function setSystem($system);

    /**
     * Get system.
     *
     * @return string
     */
    public function getSystem();

    /**
     * Get created.
     *
     * @return \DateTime
     */
    public function getCreated();

    /**
     * Get changed.
     *
     * @return \DateTime
     */
    public function getChanged();

    /**
     * Get id.
     *
     * @return int
     */
    public function getId();

    /**
     * Set creator.
     *
     * @param UserInterface $creator
     *
     * @return RoleInterface
     */
    public function setCreator(UserInterface $creator = null);

    /**
     * Get creator.
     *
     * @return UserInterface
     */
    public function getCreator();

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return RoleInterface
     */
    public function setChanger(UserInterface $changer = null);

    /**
     * Get changer.
     *
     * @return UserInterface
     */
    public function getChanger();

    /**
     * Add permissions.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     *
     * @return RoleInterface
     */
    public function addPermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions);

    /**
     * Remove permissions.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Permission $permissions
     */
    public function removePermission(\Sulu\Bundle\SecurityBundle\Entity\Permission $permissions);

    /**
     * Get permissions.
     *
     * @return Permission[]
     */
    public function getPermissions();

    /**
     * Add userRoles.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     *
     * @return UserRole
     */
    public function addUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles);

    /**
     * Remove userRoles.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles
     */
    public function removeUserRole(\Sulu\Bundle\SecurityBundle\Entity\UserRole $userRoles);

    /**
     * Get userRoles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles();

    /**
     * Add groups.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     *
     * @return RoleInterface
     */
    public function addGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups);

    /**
     * Remove groups.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\Group $groups
     */
    public function removeGroup(\Sulu\Bundle\SecurityBundle\Entity\Group $groups);

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups();

    /**
     * Set securityType.
     *
     * @param \Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType
     *
     * @return RoleInterface
     */
    public function setSecurityType(\Sulu\Bundle\SecurityBundle\Entity\SecurityType $securityType = null);

    /**
     * Get securityType.
     *
     * @return \Sulu\Bundle\SecurityBundle\Entity\SecurityType
     */
    public function getSecurityType();
}
