<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;

class UserRoleTest extends TestCase
{
    public function testAddRole(): void
    {
        $userRole = new UserRole();
        $role = new Role();
        $role->setName('User');

        $userRole->setRole($role);

        $this->assertSame($role, $userRole->getRole());
    }

    public function testAddAnonymousRole(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'It is not allowed to add an anonymous role to a user. Tried to add role "Anonymous".'
        );

        $userRole = new UserRole();
        $role = new Role();
        $role->setName('Anonymous');
        $role->setAnonymous(true);

        $userRole->setRole($role);
    }
}
