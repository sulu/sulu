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

use Sulu\Bundle\SecurityBundle\Entity\User;

trait TestUserTrait
{
    /**
     * Return the test user (which is provided / created
     * by the test_user_provider in this Bundle at runtime).
     *
     * @return User
     */
    protected static function getTestUser()
    {
        return static::getContainer()->get('test_user_provider')->getUser();
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
