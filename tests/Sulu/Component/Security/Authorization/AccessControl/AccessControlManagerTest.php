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
