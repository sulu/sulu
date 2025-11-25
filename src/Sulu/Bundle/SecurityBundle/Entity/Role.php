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

/**
 * Role.
 */
class Role implements RoleInterface
{
    use AuditableTrait;

    private int $id;

    private string $name = '';

    private ?string $key = null;

    private string $system = '';

    /** @var Collection<int, Permission> */
    #[Groups(['fullRole'])]
    private Collection $permissions;

    /** @var Collection<int, UserRole> */
    #[Exclude]
    private Collection $userRoles;

    /** @var Collection<string, RoleSettingInterface> */
    private Collection $settings;

    private bool $anonymous = false;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->userRoles = new ArrayCollection();
        $this->settings = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function getIdentifier(): string
    {
        if ($this->anonymous) {
            return RoleInterface::IS_SULU_ANONYMOUS;
        }

        $key = $this->getKey();

        // keep backwards compatibility as name was used for generating identifier before key was introduced
        if (!$key) {
            $key = $this->getName();
        }

        return 'ROLE_SULU_' . \strtoupper($key);
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function setSystem(string $system): static
    {
        $this->system = $system;

        return $this;
    }

    public function getSystem(): string
    {
        return $this->system;
    }

    public function addPermission(Permission $permissions): static
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    public function removePermission(Permission $permissions): static
    {
        $this->permissions->removeElement($permissions);

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addUserRole(UserRole $userRoles): static
    {
        $this->userRoles[] = $userRoles;

        return $this;
    }

    public function removeUserRole(UserRole $userRoles): static
    {
        $this->userRoles->removeElement($userRoles);

        return $this;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addSetting(RoleSettingInterface $setting): static
    {
        $this->settings->set($setting->getKey(), $setting);

        return $this;
    }

    public function removeSetting(RoleSettingInterface $setting): static
    {
        $this->settings->remove($setting->getKey());

        return $this;
    }

    /**
     * @return Collection<string, RoleSettingInterface>
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function getSetting(string $key): ?RoleSettingInterface
    {
        return $this->settings->get($key);
    }

    public function getAnonymous(): bool
    {
        return $this->anonymous;
    }

    public function setAnonymous(bool $anonymous): static
    {
        $this->anonymous = $anonymous;

        return $this;
    }
}
