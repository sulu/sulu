<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Factory;

use Sulu\Component\Security\Authentication\UserInterface;

interface UserFactoryInterface
{
    public function create(
        string $username,
        string $firstName,
        string $lastName,
        string $email,
        string $locale,
        string $password,
        string $roleName = 'User'
    ): UserInterface;
}
