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

use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\TwoFactorInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

interface UserInterface extends BaseUserInterface, TwoFactorInterface
{
    public const RESOURCE_KEY = 'users';

    public function getId(): int;

    public function getLocale(): string;

    /**
     * @return array<int, RoleInterface>
     */
    public function getRoleObjects(): array;

    public function getFullName(): string;

    public function getLocked(): bool;

    public function getEnabled(): bool;
}
