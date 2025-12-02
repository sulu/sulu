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

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Sulu\Component\Security\Authentication\RoleInterface;

#[ExclusionPolicy('all')]
class Permission implements \Stringable
{
    #[Expose]
    private string $context;

    #[Expose]
    private int $permissions;

    #[Expose]
    private int $id;

    private ?RoleInterface $role = null;

    public function setContext(string $context): static
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function setPermissions(int $permissions): static
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isNew(): bool
    {
        return !isset($this->id);
    }

    public function setRole(?RoleInterface $role = null): static
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): ?RoleInterface
    {
        return $this->role;
    }

    public function __toString(): string
    {
        return (string) $this->context;
    }
}
