<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Security\Authorization;

use Prophecy\Argument;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SecurityContextVoterTest extends \PHPUnit_Framework_TestCase
{
    protected $permissions = array(
        'view' => 64,
        'add' => 32,
        'edit' => 16,
        'delete' => 8,
        'archive' => 4,
        'live' => 2,
        'security' => 1
    );

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
     * @var AclProviderInterface
     */
    protected $aclProvider;

    public function setUp()
    {
        $this->user = new User();
        $this->userRole = new UserRole();
        $this->role = new Role();
        $this->permission = new Permission();
        $this->permission->setPermissions(122);
        $this->permission->setContext('sulu.security.roles');
        $this->role->addPermission($this->permission);
        $this->userRole->setRole($this->role);
        $this->user->addUserRole($this->userRole);

        $this->userGroup = new UserGroup();
        $this->group = new Group();
        $this->role = new Role();
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

        $this->aclProvider = $this->prophesize(AclProviderInterface::class);
        $this->aclProvider->findAcl(Argument::any())->willReturn(true);

        $this->voter = new SecurityContextVoter($this->permissions, $this->aclProvider->reveal());
    }

    public function testPositiveVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles'),
            array('security')
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
            array('view')
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
            array('security')
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups'),
            array('security')
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups.nested'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups.nested'),
            array('security')
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testAbstainWhenAclExistsWithoutLocalizationVote()
    {
        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.groups', null, 'Sulu\Bundle\Security\Entity\Group', '1'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $access);
    }

    public function testPositiveWhenAclExistsVote()
    {
        $this->userRole->setLocale('["de"]');

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', 'de', 'Sulu\Bundle\Security\Entity\Group', '1'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeWhenAclExistsVote()
    {
        $this->userRole->setLocale('["en"]');

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', 'de', 'Sulu\Bundle\SecurityBundle\Group', '1'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveWhenAclNotExistsVote()
    {
        $this->aclProvider->findAcl(Argument::any())->willThrow(AclNotFoundException::class);

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', null, 'Sulu\Bundle\SecurityBundle\Group', '1'),
            array('view')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testPositiveVoteWithMultipleAttributes()
    {
        $this->aclProvider->findAcl(Argument::any())->willThrow(AclNotFoundException::class);

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', null),
            array('view', 'add')
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVoteWithMultipleAttributes()
    {
        $this->aclProvider->findAcl(Argument::any())->willThrow(AclNotFoundException::class);

        $access = $this->voter->vote(
            $this->token->reveal(),
            new SecurityCondition('sulu.security.roles', null),
            array('view', 'security')
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $access);
    }
}
