<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Component\Security\Authentication\RoleInterface;

trait TestUserTrait
{
    /**
     * Return a test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return User
     */
    protected static function getTestUser(string $username = TestUserProvider::TEST_USER_USERNAME)
    {
        return static::getContainer()->get('test_user_provider')->getUser($username);
    }

    /**
     * Return a test role (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     */
    protected static function getTestRole(
        string $name,
        string $system = Admin::SULU_ADMIN_SECURITY_SYSTEM
    ): RoleInterface {
        return static::getContainer()->get('test_user_provider')->getRole($name, $system);
    }

    /**
     * Return the ID of the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return int
     */
    protected static function getTestUserId()
    {
        return static::getTestUser()->getId();
    }
}
