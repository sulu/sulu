<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization\AccessControl;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManager;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlProviderInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Event\PermissionUpdateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AccessControlManagerTest extends TestCase
{
    use ReadObjectAttributeTrait;

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

    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    public function setUp(): void
    {
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->systemStore = $this->prophesize(SystemStoreInterface::class);

        $this->maskConverter->convertPermissionsToArray(0)->willReturn(['view' => false, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(127)->willReturn([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ]);

        $this->accessControlManager = new AccessControlManager(
            $this->maskConverter->reveal(),
            $this->eventDispatcher->reveal(),
            $this->systemStore->reveal()
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
            new PermissionUpdateEvent(\stdClass::class, '1', []),
            'sulu_security.permission_update'
        )->shouldBeCalled();

        $this->accessControlManager->setPermissions(\stdClass::class, '1', []);
    }

    public function testSetPermissionsWithoutProvider()
    {
        $this->assertNull($this->accessControlManager->setPermissions(\stdClass::class, '1', []));
    }

    public function testGetPermissions()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->getPermissions(\stdClass::class, '1', null)->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->getPermissions(\stdClass::class, '1');
    }

    public function testGetPermissionsWithSystem()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->getPermissions(\stdClass::class, '1', 'Sulu')->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->getPermissions(\stdClass::class, '1', 'Sulu');
    }

    public function testGetPermissionsWithoutProvider()
    {
        $this->assertNull($this->accessControlManager->getPermissions(\stdClass::class, '1'));
    }

    /**
     * @dataProvider provideUserPermission
     */
    public function testGetUserPermissions(
        $rolePermissions,
        $securityContextPermissions,
        $userLocales,
        $locale,
        $result,
        $system
    ) {
        $this->systemStore->getSystem()->willReturn($system);

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', $system)->willReturn($rolePermissions);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        // create role for given role permissions from data provider
        /** @var Permission $permission1 */
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn($securityContextPermissions);
        $permission1->getContext()->willReturn('example');
        /** @var Role $role1 */
        $role1 = $this->prophesize(Role::class);
        $role1->getPermissions()->willReturn([$permission1->reveal()]);
        $role1->getSystem()->willReturn($system);
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
        $role2->getSystem()->willReturn($system);
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

    public function testGetUserPermissionsWithMissingRole()
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        // create role for given role permissions from data provider
        /** @var Permission $permission1 */
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        /** @var Role $role1 */
        $role1 = $this->prophesize(Role::class);
        $role1->getPermissions()->willReturn([$permission1->reveal()]);
        $role1->getSystem()->willReturn('Sulu');
        $role1->getId()->willReturn(1);
        /** @var UserRole $userRole1 */
        $userRole1 = $this->prophesize(UserRole::class);
        $userRole1->getRole()->willReturn($role1->reveal());
        $userRole1->getLocales()->willReturn(['de', 'en']);

        // return the user with the above definitions
        /** @var User $user */
        $user = $this->prophesize(User::class);
        $user->getUserRoles()->willReturn([$userRole1->reveal()]);
        $user->getRoleObjects()->willReturn([$role1->reveal()]);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            $user->reveal()
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testGetUserPermissionsWithoutUser()
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        /** @var Permission $permission1 */
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        /** @var Role $role1 */
        $anonymousRole = $this->prophesize(Role::class);
        $anonymousRole->getPermissions()->willReturn([$permission1->reveal()]);
        $anonymousRole->getSystem()->willReturn('Sulu');
        $anonymousRole->getId()->willReturn(1);

        $this->systemStore->getAnonymousRole()->willReturn($anonymousRole);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            null
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testGetUserPermissionsWithoutSystem()
    {
        $this->systemStore->getSystem()->willReturn(null);

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(Argument::cetera())->shouldNotBeCalled();
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            null
        );

        $this->assertEquals([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ], $permissions);
    }

    public function testGetUserPermissionByArrayWithoutSystem()
    {
        $this->systemStore->getSystem()->willReturn(null);

        $permissions = $this->accessControlManager->getUserPermissionByArray(
            'de',
            'sulu_page.pages',
            [],
            null
        );

        $this->assertEquals([
            'view' => true,
            'edit' => true,
            'add' => true,
            'delete' => true,
            'live' => true,
            'security' => true,
            'archive' => true,
        ], $permissions);
    }

    public function testGetUserPermissionsWithoutAnonymousUser()
    {
        $this->systemStore->getSystem()->willReturn('Sulu');

        /** @var AccessControlProviderInterface $accessControlProvider */
        $accessControlProvider = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider->supports(\stdClass::class)->willReturn(true);
        $accessControlProvider->getPermissions(\stdClass::class, '1', 'Sulu')
            ->willReturn([2 => ['view' => true, 'edit' => true]]);
        $this->accessControlManager->addAccessControlProvider($accessControlProvider->reveal());

        /** @var Permission $permission1 */
        $permission1 = $this->prophesize(Permission::class);
        $permission1->getPermissions()->willReturn(64);
        $permission1->getContext()->willReturn('example');
        /** @var Role $role1 */
        $anonymousRole = $this->prophesize(Role::class);
        $anonymousRole->getPermissions()->willReturn([$permission1->reveal()]);
        $anonymousRole->getSystem()->willReturn('Sulu');
        $anonymousRole->getId()->willReturn(1);

        $this->systemStore->getAnonymousRole()->willReturn($anonymousRole);

        $permissions = $this->accessControlManager->getUserPermissions(
            new SecurityCondition('example', 'de', \stdClass::class, '1'),
            'anon.'
        );

        $this->assertEquals(['view' => true, 'edit' => false], $permissions);
    }

    public function testAddAccessControlProvider()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->assertCount(2, $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders'));
        $this->assertContains(
            $accessControlProvider1->reveal(),
            $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders')
        );
        $this->assertContains(
            $accessControlProvider2->reveal(),
            $this->readObjectAttribute($this->accessControlManager, 'accessControlProviders')
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
                'Sulu',
            ],
            [
                [1 => ['view' => false, 'edit' => false]],
                96,
                ['de', 'en'],
                'de',
                ['view' => false, 'edit' => false],
                'Sulu',
            ],
            [
                [],
                64,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => false],
                'Sulu',
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                0,
                ['de', 'en'],
                'de',
                ['view' => true, 'edit' => true],
                'Sulu',
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
                'Sulu',
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
                'Website',
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
                'Sulu',
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
                'Sulu',
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
                'Website',
            ],
            [
                [1 => ['view' => true, 'edit' => true]],
                96,
                ['en'],
                'de',
                ['view' => false, 'edit' => false],
                'Sulu',
            ],
        ];
    }
}
