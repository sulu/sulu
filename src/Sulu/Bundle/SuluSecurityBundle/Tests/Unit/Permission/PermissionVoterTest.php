<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Permission\Controller;

use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserGroup;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\SecurityBundle\Permission\PermissionVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoterTest extends \PHPUnit_Framework_TestCase
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
     * @var TokenInterface
     */
    protected $token;

    /**
     * @var PermissionVoter
     */
    protected $voter;

    public function setUp()
    {
        $user = new User();
        $userRole = new UserRole();
        $role = new Role();
        $permission = new Permission();
        $permission->setPermissions(122);
        $permission->setContext('sulu.security.roles');
        $role->addPermission($permission);
        $userRole->setRole($role);
        $user->addUserRole($userRole);

        $userGroup = new UserGroup();
        $group = new Group();
        $role = new Role();
        $permission = new Permission();
        $permission->setPermissions(122);
        $permission->setContext('sulu.security.groups');
        $role->addPermission($permission);
        $group->addRole($role);
        $userGroup->setGroup($group);

        $nestedGroup = new Group();
        $role = new Role();
        $permission = new Permission();
        $permission->setPermissions(122);
        $permission->setContext('sulu.security.groups.nested');
        $role->addPermission($permission);
        $nestedGroup->addRole($role);
        $group->addChildren($nestedGroup);
        $user->addUserGroup($userGroup);

        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->voter = new PermissionVoter($this->permissions);
    }

    public function testPositiveVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.roles',
            array(
                'permission' => 'view'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.roles',
            array(
                'permission' => 'security'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveGroupVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.groups',
            array(
                'permission' => 'view'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeGroupVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.groups',
            array(
                'permission' => 'security'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }

    public function testPositiveNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.groups.nested',
            array(
                'permission' => 'view'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    public function testNegativeNestedGroupVote()
    {
        $access = $this->voter->vote(
            $this->token,
            'sulu.security.groups.nested',
            array(
                'permission' => 'security'
            )
        );

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }
}
