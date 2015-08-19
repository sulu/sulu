<?php
/*
 * This file is part of Sulu
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Component\Security\Authorization\AccessControl;

use Doctrine\Common\Persistence\ObjectManager;
use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
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
     * @var MaskConverterInterface
     */
    private $maskConverter;

    public function setUp()
    {
        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->roleRepository = $this->prophesize(RoleRepositoryInterface::class);
        $this->maskConverter = $this->prophesize(MaskConverterInterface::class);

        $this->doctrineAccessControlProvider = new DoctrineAccessControlProvider(
            $this->objectManager->reveal(),
            $this->roleRepository->reveal(),
            $this->maskConverter->reveal()
        );
    }

    public function testGetPermissions()
    {
        $this->doctrineAccessControlProvider->getPermissions('AcmeBundle\Example', 1);
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
