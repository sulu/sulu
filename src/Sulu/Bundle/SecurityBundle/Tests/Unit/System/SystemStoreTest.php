<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\System;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\SecurityBundle\System\SystemStore;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;

class SystemStoreTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RoleRepositoryInterface>
     */
    private $roleRepository;

    /**
     * @var SystemStore
     */
    private $systemStore;

    public function setUp(): void
    {
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->systemStore = new SystemStore($this->roleRepository->reveal());
    }

    public function testSetSystem(): void
    {
        $this->systemStore->setSystem('Sulu');
        $this->assertEquals('Sulu', $this->systemStore->getSystem());
        $this->systemStore->setSystem('Sulu Test');
        $this->assertEquals('Sulu Test', $this->systemStore->getSystem());
    }

    public function testGetAnonymousRoleWithLazyLoading(): void
    {
        $role = $this->prophesize(RoleInterface::class);
        $this->roleRepository
            ->findAllRoles(['anonymous' => true, 'system' => 'Sulu'])
            ->willReturn([$role->reveal()])
            ->shouldBeCalledTimes(1);
        $this->systemStore->setSystem('Sulu');
        $this->assertEquals($role->reveal(), $this->systemStore->getAnonymousRole());
        $this->assertEquals($role->reveal(), $this->systemStore->getAnonymousRole());
    }

    public function testGetNonExistingAnonymousRole(): void
    {
        $this->systemStore->setSystem('Sulu');
        $this->assertEquals(null, $this->systemStore->getAnonymousRole());
    }
}
