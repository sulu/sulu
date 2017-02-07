<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Tests\Unit\Authorization;

use Sulu\Bundle\SecurityBundle\Entity\BaseRole;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
use Sulu\Component\Security\Authorization\SecurityCondition;
use Sulu\Component\Security\Authorization\SecurityContextVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SecurityContextVoterTest extends \PHPUnit_Framework_TestCase
{
    protected $permissions = [
        'view' => 64,
        'add' => 32,
        'edit' => 16,
        'delete' => 8,
        'archive' => 4,
        'live' => 2,
        'security' => 1,
    ];

    /**
     * @var User
     */
    protected $user;

    /**
     * @var UserRole
     */
    protected $userRole;

    /**
     * @var Role
     */
    protected $role;

    /**
     * @var Permission
     */
    protected $permission;

    /**
     * @var UserGroup
     */
    protected $userGroup;

    /**
     * @var Group
     */
    protected $group;

    /**
     * @var Group
     */
    protected $nestedGroup;

    /**
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var SecurityContextVoter
     */
    protected $voter;

    /**
     * @var AccessControlManagerInterface
     */
    protected $accessControlManager;

    public function setUp()
    {
        $roleIdReflection = new \ReflectionProperty(BaseRole::class, 'id');
        $roleIdReflection->setAccessible(true);

        $this->user = new User();
        $this->userRole = new UserRole();
        $this->role = new Role();
        $roleIdReflection->setValue($this->role, 1);
        $this->role->setName('role1');
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.roles');
        $this->role->addPermission($this->permission);
        $this->userRole->setRole($this->role);
        $this->user->addUserRole($this->userRole);

        $this->token = $this->prophesize(TokenInterface::class);
        $this->token->getUser()->willReturn($this->user);

        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);

        $this->voter = new SecurityContextVoter($this->accessControlManager->reveal(), $this->permissions);
    }

    public function testPositiveVote()
    {
        $securityCondition = new SecurityCondition('sulu.security.roles');

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'edit' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVote()
    {
        $securityCondition = new SecurityCondition('sulu.security.roles');

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'edit' => false,
            'security' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveVoteWithMultipleAttributes()
    {
        $securityCondition = new SecurityCondition('sulu.security.roles', null);

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'add' => true,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view', 'add']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVoteWithMultipleAttributes()
    {
        $securityCondition = new SecurityCondition('sulu.security.roles', null);

        $this->accessControlManager->getUserPermissions($securityCondition, $this->user)->willReturn([
            'view' => true,
            'add' => true,
            'security' => false,
        ]);

        $access = $this->voter->vote(
            $this->token->reveal(),
            $securityCondition,
            ['view', 'security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }
}
