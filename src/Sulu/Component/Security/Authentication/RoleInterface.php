<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authentication;

use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Persistence\Model\AuditableInterface;

/**
 * Defines the interface for a role.
 */
interface RoleInterface extends AuditableInterface, SecurityIdentityInterface
{
    const IS_SULU_ANONYMOUS = 'IS_SULU_ANONYMOUS';

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
     * Set key.
     *
     * @param string $key
     *
     * @return RoleInterface
     */
    public function setKey($key);

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey();

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
    public function setCreator($creator);

    /**
     * Set changer.
     *
     * @param UserInterface $changer
     *
     * @return RoleInterface
     */
    public function setChanger($changer);

    /**
     * Add permissions.
     *
     * @return RoleInterface
     */
    public function addPermission(Permission $permissions);

    /**
     * Remove permissions.
     */
    public function removePermission(Permission $permissions);

    /**
     * Get permissions.
     *
     * @return Permission[]
     */
    public function getPermissions();

    /**
     * Add userRoles.
     *
     * @return UserRole
     */
    public function addUserRole(UserRole $userRoles);

    /**
     * Remove userRoles.
     */
    public function removeUserRole(UserRole $userRoles);

    /**
     * Get userRoles.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserRoles();

    /**
     * Add groups.
     *
     * @return RoleInterface
     */
    public function addGroup(Group $groups);

    /**
     * Remove groups.
     */
    public function removeGroup(Group $groups);

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups();

    /**
     * Set securityType.
     *
     * @param SecurityType $securityType
     *
     * @return RoleInterface
     */
    public function setSecurityType(SecurityType $securityType = null);

    /**
     * Get securityType.
     *
     * @return SecurityType
     */
    public function getSecurityType();

    /**
     * Returns setting by name.
     *
     * @param string $key
     *
     * @return RoleSettingInterface|null
     */
    public function getSetting($key);

    /**
     * Returns if the role is of type anonymous and is handled like IS_ANONYMOUS in Symfony.
     * See https://symfony.com/doc/5.1/security.html#checking-to-see-if-a-user-is-logged-in-is-authenticated-fully documentation.
     */
    public function getAnonymous(): bool;

    /**
     * Set if the role is IS_ANONYMOUS.
     */
    public function setAnonymous(bool $anonymous);
}
