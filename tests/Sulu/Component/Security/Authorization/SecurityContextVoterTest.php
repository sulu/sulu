<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Component\Security\Authorization\AccessControl\AccessControlManagerInterface;
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
        $this->user = new User();
        $this->userRole = new UserRole();
        $this->role = new Role();
        $this->role->setName('role1');
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.roles');
        $this->role->addPermission($this->permission);
        $this->userRole->setRole($this->role);
        $this->user->addUserRole($this->userRole);

        $this->userGroup = new UserGroup();
        $this->group = new Group();
        $this->role = new Role();
        $this->role->setName('role2');
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.groups');
        $this->role->addPermission($this->permission);
        $this->group->addRole($this->role);
        $this->userGroup->setGroup($this->group);

        $this->nestedGroup = new Group();
        $this->role = new Role();
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.groups.nested');
        $this->role->addPermission($this->permission);
        $this->nestedGroup->addRole($this->role);
        $this->group->addChildren($this->nestedGroup);
        $this->user->addUserGroup($this->userGroup);

        $this->token = $this->prophesize(TokenInterface::class);
        $this->token->getUser()->willReturn($this->user);

        $this->accessControlManager = $this->prophesize(AccessControlManagerInterface::class);

        $this->voter = new SecurityContextVoter($this->accessControlManager->reveal(), $this->permissions);
    }

    public function testPositiveVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveVoteWithoutGroup()
    {
        foreach ($this->user->getUserGroups() as $userGroup) {
            $this->user->removeUserGroup($userGroup);
        }

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVoteWithoutGroup()
    {
        foreach ($this->user->getUserGroups() as $userGroup) {
            $this->user->removeUserGroup($userGroup);
        }

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups.nested'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups.nested'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveLocaleVote()
    {
        $this->userRole->setLocale('["de"]');

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', 'de', 'Sulu\Bundle\Security\Entity\Group', '1'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeLocaleExistsVote()
    {
        $this->userRole->setLocale('["en"]');

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', 'de', 'Sulu\Bundle\SecurityBundle\Group', '1'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveVoteWithMultipleAttributes()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', null),
            ['view', 'add']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVoteWithMultipleAttributes()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', null),
            ['view', 'security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveObjectVote()
    {
        $this->accessControlManager->getPermissions('Sulu\Bundle\SecurityBundle\Group', '1')->willReturn(
            [
                'ROLE_SULU_ROLE1' => [
                    'view' => true,
                ],
            ]
        );

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups', null, 'Sulu\Bundle\SecurityBundle\Group', '1'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testPositiveObjectVoteWithNegativeSecurityContextVote()
    {
        $this->accessControlManager->getPermissions('Sulu\Bundle\SecurityBundle\Group', '1')->willReturn(
            [
                'ROLE_SULU_ROLE1' => [
                    'security' => true,
                ],
            ]
        );

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups', null, 'Sulu\Bundle\SecurityBundle\Group', '1'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeSecurityContextVoteWithEmptyObjectSecurity()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups', null, 'Sulu\Bundle\SecurityBundle\Group', '1'),
            ['security']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testNegativeObjectVoteWithPositiveSecurityContextVote()
    {
        $this->accessControlManager->getPermissions('Sulu\Bundle\SecurityBundle\Group', '1')->willReturn(
            [
                'ROLE_SULU_ROLE1' => [
                    'view' => false,
                ],
            ]
        );

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups', null, 'Sulu\Bundle\SecurityBundle\Group', '1'),
            ['view']
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }
}
