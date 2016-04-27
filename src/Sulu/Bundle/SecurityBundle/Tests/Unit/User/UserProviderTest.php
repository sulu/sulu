<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\User;

use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\User\UserProvider;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var UserProvider
     */
    private $userProvider;

    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        $this->userRepository = $this->prophesize(UserRepositoryInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->userProvider = new UserProvider($this->userRepository->reveal(), $this->requestStack->reveal(), 'Sulu');

        $this->user = new User();
        $this->user->setUsername('sulu');
        $this->user->setEmail('test@sulu.io');

        $this->userRepository->findUserWithSecurityById($this->user->getId())->willReturn($this->user);
        $this->userRepository->findUserByIdentifier('sulu')->willReturn($this->user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\DisabledException
     */
    public function testLoginFailDisabledUser()
    {
        $this->user->setEnabled(false);
        $this->userProvider->loadUserByUsername('sulu');
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\LockedException
     */
    public function testLoginFailLockedUser()
    {
        $this->user->setLocked(true);
        $this->userProvider->loadUserByUsername('sulu');
    }

    public function testLoadUserByUsername()
    {
        $role = new Role();
        $role->setSystem('Sulu');
        $userRole = new UserRole();
        $userRole->setRole($role);
        $this->user->addUserRole($userRole);
        $user = $this->userProvider->loadUserByUsername('sulu');

        $this->assertEquals('test@sulu.io', $user->getEmail());
    }

    public function testRefreshUser()
    {
        $this->assertEquals($this->user->getUsername(), $this->userProvider->refreshUser($this->user)->getUsername());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\LockedException
     */
    public function testRefreshUserWithLockedUser()
    {
        $this->user->setLocked(true);
        $this->userProvider->refreshUser($this->user);
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\DisabledException
     */
    public function testRefreshUserWithDisabledUser()
    {
        $this->user->setEnabled(false);
        $this->userProvider->refreshUser($this->user);
    }
}
