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

use Doctrine\Common\Collections\Collection;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Persistence\Model\AuditableInterface;

interface RoleInterface extends AuditableInterface, SecurityIdentityInterface
{
    public const RESOURCE_KEY = 'roles';
    public const IS_SULU_ANONYMOUS = 'IS_SULU_ANONYMOUS';

    public function setName(string $name): static;

    public function getName(): string;

    public function setKey(?string $key): static;

    public function getKey(): ?string;

    public function setSystem(string $system): static;

    public function getSystem(): string;

    public function getId(): int;

    public function isNew(): bool;

    public function addPermission(Permission $permissions): static;

    public function removePermission(Permission $permissions): static;

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection;

    public function addUserRole(UserRole $userRoles): static;

    public function removeUserRole(UserRole $userRoles): static;

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection;

    public function getSetting(string $key): ?RoleSettingInterface;

    public function getAnonymous(): bool;

    public function setAnonymous(bool $anonymous): static;
}
