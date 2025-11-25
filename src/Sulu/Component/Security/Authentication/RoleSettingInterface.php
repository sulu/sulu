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

interface RoleSettingInterface
{
    public function getId(): int;

    public function setKey(string $key): static;

    public function getKey(): string;

    public function setValue(mixed $value): static;

    public function getValue(): mixed;

    public function setRole(?RoleInterface $role = null): static;

    public function getRole(): ?RoleInterface;
}
