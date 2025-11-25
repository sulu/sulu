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

use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlInterface;

class AccessControl implements AccessControlInterface
{
    private int $id;

    private RoleInterface $role;

    private int $permissions;

    private string $entityId;

    private ?int $entityIdInteger = null;

    private string $entityClass;

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function getRole(): RoleInterface
    {
        return $this->role;
    }

    public function setRole(RoleInterface $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function setPermissions(int $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getEntityId(): string|int
    {
        if ($this->entityIdInteger) {
            return $this->entityIdInteger;
        }

        return $this->entityId;
    }

    public function setEntityId(string|int $entityId): static
    {
        $this->entityId = (string) $entityId;

        if (\is_numeric($entityId)) {
            $this->entityIdInteger = (int) $entityId;
        }

        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;

        return $this;
    }
}
