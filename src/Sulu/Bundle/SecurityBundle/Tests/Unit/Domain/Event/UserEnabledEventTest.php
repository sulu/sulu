<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserEnabledEvent;
use Sulu\Component\Security\Authentication\UserInterface;

class UserEnabledEventTest extends TestCase
{
    public function testGetEventType(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserEnabledEvent($user->reveal());

        $this->assertSame($event->getEventType(), 'enabled');
    }

    public function testGetResourceKey(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserEnabledEvent($user->reveal());

        $this->assertSame('users', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->shouldBeCalled()->willReturn(1);
        $event = new UserEnabledEvent($user->reveal());

        $this->assertSame('1', $event->getResourceId());
    }

    public function testGetResourceSecurityContext(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserEnabledEvent($user->reveal());

        $this->assertSame('sulu.security.users', $event->getResourceSecurityContext());
    }
}
