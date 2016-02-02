<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization\AccessControl;

use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlProviderInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AccessControlManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccessControlManager
     */
    private $accessControlManager;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function setUp()
    {
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->accessControlManager = new AccessControlManager(
            $this->maskConverter->reveal(),
            $this->eventDispatcher->reveal()
        );
    }

    public function testSetPermissions()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->setPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->setPermissions(\stdClass::class, '1', [])->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->eventDispatcher->dispatch(
            'sulu_security.permission_update',
            new PermissionUpdateEvent(\stdClass::class, '1', [])
        )->shouldBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', []);
    }

    public function testSetPermissionsWithoutProvider()
    {
        $this->accessControlManager->setPermissions(\stdClass::class, '1', []);
    }

    public function testGetPermissions()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->getPermissions(\stdClass::class, '1')->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->getPermissions(\stdClass::class, '1');
    }

    public function testGetPermissionsWithoutProvider()
    {
        $this->accessControlManager->getPermissions(\stdClass::class, '1');
    }

    /**
     * @dataProvider provideUserPermission
     */
    public function testGetUserPermissions(
        $rolePermissions,
        $securityContextPermissions,
        $userLocales,
        $locale,
        $result
    ) {
        $this->maskConverter->convertPermissionsToArray(0)->willReturn(['view' => false, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1')->willReturn($rolePermissions);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        // create role for given role permissions from data provider
        /** @var Permission $permission1 */
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn($securityContextPermissions);
        $permission1->getContext()->willReturn('example');
        /** @var Role $role1 */
        $role1 = $this->prophesize(Role::class);
        $role1->getPermissions()->willReturn([$permission1->reveal()]);
        $role1->getId()->willReturn(1);
        /** @var UserRole $userRole1 */
        $userRole1 = $this->prophesize(UserRole::class);
        $userRole1->getRole()->willReturn($role1->reveal());
        $userRole1->getLocales()->willReturn($userLocales);

        // add a role which should not influence the security context check
        /** @var Permission $permission */
        $permission2 = $this->prophesize(Permission::class);
        $permission2->getPermissions()->willReturn(127);
        $permission2->getContext()->willReturn('not-important');
        /** @var Role $role */
        $role2 = $this->prophesize(Role::class);
        $role2->getPermissions()->willReturn([$permission2->reveal()]);
        $role2->getId()->willReturn(2);
        /** @var UserRole $userRole */
        $userRole2 = $this->prophesize(UserRole::class);
        $userRole2->getRole()->willReturn($role2->reveal());
        $userRole2->getLocales()->willReturn($userLocales);

        // return the user with the above definitions
        /** @var User $user */
        $user = $this->prophesize(User::class);
        $user->getUserRoles()->willReturn([$userRole1->reveal(), $userRole2->reveal()]);
        $user->getRoleObjects()->willReturn([$role1->reveal(), $role2->reveal()]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', $locale, \stdClass::class, '1'),
            $user->reveal()
        );

        $this->assertEquals($result, $permissions);
    }

    public function testAddAccessControlProvider()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->assertAttributeCount(2, 'accessControlProviders', $this->accessControlManager);
        $this->assertAttributeContains(
            $accessControlProvider1->reveal(),
            'accessControlProviders',
            $this->accessControlManager
        );
        $this->assertAttributeContains(
            $accessControlProvider2->reveal(),
            'accessControlProviders',
            $this->accessControlManager
        );
    }

    public function provideUserPermission()
    {
        return [
            [
                [1 => ['view' => true, 'edit' => false]],
                32,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => false],
            ],
            [
                [1 => ['view' => false, 'edit' => false]],
                96,
                ['de', 'en'],
                'de',
                ['view' => false, 'edit' => false],
            ],
            [
                [],
                64,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => false],
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
            ],
            [
                [
                    1 => ['view' => true, 'edit' => true],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
            ],
            [
                [
                    1 => ['view' => false, 'edit' => false],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => false, 'edit' => false],
            ],
            [
                [
                    1 => ['view' => true, 'edit' => false],
                    2 => ['view' => false, 'edit' => true],
                ],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
            ],
            [
                [
                    1 => ['view' => true, 'edit' => false],
                    2 => ['view' => false, 'edit' => true],
                ],
                0,
                ['de', 'en'],
                'fr',
                ['view' => false, 'edit' => false],
            ],
            [
                [
                    1 => ['view' => true, 'edit' => true],
                    2 => ['view' => false, 'edit' => false],
                ],
                0,
                ['de', 'en'],
                null,
                ['view' => true, 'edit' => true],
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                96,
                ['en'],
                'de',
                ['view' => false, 'edit' => false],
            ],
        ];
    }
}
