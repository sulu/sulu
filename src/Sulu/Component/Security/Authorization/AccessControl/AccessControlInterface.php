<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization\AccessControl;

use Sulu\Component\Security\Authentication\RoleInterface;

interface AccessControlInterface
{
    public function getId(): int;

    public function getRole(): RoleInterface;

    public function setRole(RoleInterface $role): static;

    public function getPermissions(): int;

    public function setPermissions(int $permissions): static;

    public function getEntityId(): string|int;

    public function setEntityId(string|int $entityId): static;

    public function getEntityClass(): string;

    public function setEntityClass(string $entityClass): static;
}
