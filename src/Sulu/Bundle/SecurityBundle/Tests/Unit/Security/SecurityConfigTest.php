<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security;

use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface as SymfonyUserInterface;

class SecurityConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SecurityConfig
     */
    private $securityConfig;

    /**
     * @var AccessControlManagerInterface
     */
    private $accessControlManager;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TokenInterface
     */
    private $token;

    /**
     * @var UserInterface
     */
    private $user;

    public function setUp()
    {
        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);
        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->tokenStorage = $this->prophesize(TokenStorageInterface::class);
        $this->token = $this->prophesize(TokenInterface::class);
        $this->user = $this->prophesize(UserInterface::class);
        $this->token->getUser()->willReturn($this->user->reveal());
        $this->tokenStorage->getToken()->willReturn($this->token->reveal());
        $this->securityConfig = new SecurityConfig(
            'sulu_security.contexts',
            $this->accessControlManager->reveal(),
            $this->adminPool->reveal(),
            $this->tokenStorage->reveal()
        );
    }

    public function testGetName()
    {
        $this->assertEquals('sulu_security.contexts', $this->securityConfig->getName());
    }

    public function testNoToken()
    {
        $this->tokenStorage->getToken()->willReturn(null)->shouldBeCalled();
        $this->adminPool->getSecurityContexts()->shouldNotBeCalled();
        $this->securityConfig->getParameters();
    }

    public function testNoSuluUser()
    {
        $user = $this->prophesize(SymfonyUserInterface::class);
        $this->token->getUser()->willReturn($user)->shouldBeCalled();
        $this->adminPool->getSecurityContexts()->shouldNotBeCalled();
        $this->securityConfig->getParameters();
    }

    public function testGetParametersEmpty()
    {
        $this->adminPool->getSecurityContexts()->willReturn([])->shouldBeCalled();
        $this->assertEquals([], $this->securityConfig->getParameters());
    }

    public function testGetParameters()
    {
        $this->adminPool->getSecurityContexts()->willReturn([
            'Sulu' => [
                'Security' => [
                    'sulu.security.groups' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'sulu.security.roles' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                    'sulu.security.users' => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $this->accessControlManager->getUserPermissions(Argument::type(SecurityCondition::class), $this->user->reveal())
            ->willReturn(
                [PermissionTypes::VIEW],
                [PermissionTypes::ADD, PermissionTypes::EDIT],
                [PermissionTypes::DELETE]
            )->shouldBeCalled();

        $this->assertEquals([
            'sulu.security.groups' => [PermissionTypes::VIEW],
            'sulu.security.roles' => [PermissionTypes::ADD, PermissionTypes::EDIT],
            'sulu.security.users' => [PermissionTypes::DELETE],
        ], $this->securityConfig->getParameters());
    }
}
