<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\Tests\Unit\Request;

use Sulu\Bundle\AudienceTargetingBundle\UserContext\UserContextStore;

class UserContextStoreTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserContextStore
     */
    private $userContextStore;

    public function setUp()
    {
        $this->userContextStore = new UserContextStore();
    }

    public function testSetUserContext()
    {
        $this->userContextStore->setUserContext('2');
        $this->assertEquals('2', $this->userContextStore->getUserContext());
    }

    public function testChangeUserContext()
    {
        $this->userContextStore->setUserContext('2');
        $this->assertEquals('2', $this->userContextStore->getUserContext());
        $this->assertFalse($this->userContextStore->hasChanged());

        $this->userContextStore->updateUserContext('3');
        $this->assertEquals('3', $this->userContextStore->getUserContext());
        $this->assertTrue($this->userContextStore->hasChanged());
    }

    public function testChangeUserContextToSame()
    {
        $this->userContextStore->setUserContext('2');
        $this->userContextStore->updateUserContext('2');
        $this->assertEquals('2', $this->userContextStore->getUserContext());
        $this->assertFalse($this->userContextStore->hasChanged());
    }

    public function testChangeUserContextToSameDifferentType()
    {
        $this->userContextStore->setUserContext('2');
        $this->userContextStore->updateUserContext(2);
        $this->assertEquals('2', $this->userContextStore->getUserContext());
        $this->assertFalse($this->userContextStore->hasChanged());
    }
}
