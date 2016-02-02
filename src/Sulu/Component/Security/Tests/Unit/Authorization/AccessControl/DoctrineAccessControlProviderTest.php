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

use Doctrine\Common\Persistence\ObjectManager;
use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\BaseRole;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlRepositoryInterface;
use Sulu\Component\Security\Authorization\AccessControl\DoctrineAccessControlProvider;
use Sulu\Component\Security\Authorization\AccessControl\SecuredEntityInterface;
use Sulu\Component\Security\Authorization\MaskConverterInterface;

class DoctrineAccessControlProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DoctrineAccessControlProvider
     */
    private $doctrineAccessControlProvider;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    /**
     * @var AccessControlRepositoryInterface
     */
    private $accessControlRepository;

    /**
     * @var MaskConverterInterface
     */
    private $maskConverter;

    public function setUp()
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->accessControlRepository = $this->prophesize(AccessControlRepositoryInterface::class);
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);

        $this->doctrineAccessControlProvider = new DoctrineAccessControlProvider(
            $this->objectManager->reveal(),
            $this->roleRepository->reveal(),
            $this->accessControlRepository->reveal(),
            $this->maskConverter->reveal()
        );
    }

    public function testSetPermissions()
    {
        $role1 = new Role();
        $role2 = new Role();
        $this->roleRepository->findRoleById(1)->willReturn($role1);
        $this->roleRepository->findRoleById(2)->willReturn($role2);

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);
        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => true])->willReturn(96);

        $accessControl1 = new AccessControl();
        $accessControl1->setEntityClass('AcmeBundle\Example');
        $accessControl1->setEntityId(1);
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1);

        $accessControl2 = new AccessControl();
        $accessControl2->setEntityClass('AcmeBundle\Example');
        $accessControl2->setEntityId(1);
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2);

        $this->objectManager->persist($accessControl1)->shouldBeCalled();
        $this->objectManager->persist($accessControl2)->shouldBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testSetPermissionsWithExistingAccessControl()
    {
        $role = new Role();
        $this->roleRepository->findRoleById(1)->willReturn($role);

        $this->maskConverter->convertPermissionsToNumber(['view' => true, 'edit' => false])->willReturn(64);

        $accessControl = $this->prophesize(AccessControl::class);
        $accessControl->setPermissions(64)->shouldBeCalled();

        $this->accessControlRepository->findByTypeAndIdAndRole('AcmeBundle\Example', 1, 1)->willReturn($accessControl);

        $this->objectManager->persist(Argument::any())->shouldNotBeCalled();
        $this->objectManager->flush()->shouldBeCalled();

        $this->doctrineAccessControlProvider->setPermissions(
            'AcmeBundle\Example',
            1,
            [
                1 => ['view' => true, 'edit' => false],
            ]
        );
    }

    public function testGetPermissions()
    {
        $roleIdReflection = new \ReflectionProperty(BaseRole::class, 'id');
        $roleIdReflection->setAccessible(true);

        $role1 = new Role();
        $roleIdReflection->setValue($role1, 1);

        $role2 = new Role();
        $roleIdReflection->setValue($role2, 2);

        $this->maskConverter->convertPermissionsToArray(64)->willReturn(['view' => true, 'edit' => false]);
        $this->maskConverter->convertPermissionsToArray(96)->willReturn(['view' => true, 'edit' => true]);

        $accessControl1 = new AccessControl();
        $accessControl1->setPermissions(64);
        $accessControl1->setRole($role1);

        $accessControl2 = new AccessControl();
        $accessControl2->setPermissions(96);
        $accessControl2->setRole($role2);

        $accessControls = [
            $accessControl1,
            $accessControl2,
        ];
        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)->willReturn($accessControls);

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1),
            [
                1 => ['view' => true, 'edit' => false],
                2 => ['view' => true, 'edit' => true],
            ]
        );
    }

    public function testGetPermissionsForNotExistingAccessControl()
    {
        $this->accessControlRepository->findByTypeAndId('AcmeBundle\Example', 1)->willReturn([]);

        $this->assertEquals(
            $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1),
            []
        );
    }

    /**
     * @dataProvider provideSupport
     */
    public function testSupport($type, $supported)
    {
        $this->assertSame($supported, $this->doctrineAccessControlProvider->supports($type));
    }

    public function provideSupport()
    {
        $securedEntity = $this->prophesize(SecuredEntityInterface::class);

        return [
            [\stdClass::class, false],
            [get_class($securedEntity->reveal()), true],
        ];
    }
}
