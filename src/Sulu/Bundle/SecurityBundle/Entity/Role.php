<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleSettingInterface;
use Symfony\Component\Security\Core\Role\Role as SymfonyRole;

/**
 * Role.
 */
class Role extends SymfonyRole implements RoleInterface
{
    use AuditableTrait;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $system;

    /**
     * @var SecurityType
     */
    private $securityType;

    /**
     * @var Collection|Permission[]
     * @var Collection
     * @Groups({"fullRole"})
     */
    private $permissions;

    /**
     * @var Collection|UserRole[]
     *
     * @Exclude
     */
    private $userRoles;

    /**
     * @var Collection|Group[]
     *
     * @Exclude
     */
    private $groups;

    /**
     * @var Collection|RoleSettingInterface[]
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->settings = new ArrayCollection();

        parent::__construct('');
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
     * {@inheritdoc}
     */
    public function getRole()
    {
        return 'ROLE_SULU_' . strtoupper($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'ROLE_SULU_' . strtoupper($this->getName());
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Role
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
     * Set system.
     *
     * @param string $system
     *
     * @return Role
     */
    public function setSystem($system)
    {
        $this->system = $system;

        return $this;
    }

    /**
     * Get system.
     *
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * Set securityType.
     *
     * @param SecurityType $securityType
     *
     * @return Role
     */
    public function setSecurityType(SecurityType $securityType = null)
    {
        $this->securityType = $securityType;

        return $this;
    }

    /**
     * Get securityType.
     *
     * @return SecurityType
     */
    public function getSecurityType()
    {
        return $this->securityType;
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
     * @return Collection|Permission[]
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
     * @return Collection|UserRole[]
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
     * @return Collection|Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add setting.
     *
     * @param RoleSettingInterface $setting
     *
     * @return Role
     */
    public function addSetting(RoleSettingInterface $setting)
    {
        $this->settings->set($setting->getKey(), $setting);

        return $this;
    }

    /**
     * Remove setting.
     *
     * @param RoleSettingInterface $setting
     */
    public function removeSetting(RoleSettingInterface $setting)
    {
        $this->settings->remove($setting->getKey());
    }

    /**
     * Get settings.
     *
     * @return Collection|RoleSettingInterface[]
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetting($key)
    {
        return $this->settings->get($key);
    }
}
