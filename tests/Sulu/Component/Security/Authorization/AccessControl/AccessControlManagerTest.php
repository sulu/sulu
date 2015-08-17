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

use Prophecy\Argument;

class AccessControlManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AccessControlManager
     */
    private $accessControlManager;

    public function setUp()
    {
        $this->accessControlManager = new AccessControlManager();
    }

    public function testSetPermissions()
    {
        $accessControlProvider1 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider1->supports(Argument::any())->willReturn(false);
        $accessControlProvider1->setPermissions(Argument::cetera())->shouldNotBeCalled();
        $accessControlProvider2 = $this->prophesize(AccessControlProviderInterface::class);
        $accessControlProvider2->supports(Argument::any())->willReturn(true);
        $accessControlProvider2->setPermissions(\stdClass::class, '1', 'securityIdentity', [])->shouldBeCalled();

        $this->accessControlManager->addAccessControlProvider($accessControlProvider1->reveal());
        $this->accessControlManager->addAccessControlProvider($accessControlProvider2->reveal());

        $this->accessControlManager->setPermissions(\stdClass::class, '1', 'securityIdentity', []);
    }

    public function testSetPermissionsWithoutProvider()
    {
        $this->accessControlManager->setPermissions(\stdClass::class, '1', 'securityIdentity', []);
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
}
