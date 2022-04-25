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
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\SecurityBundle\Domain\Event\UserCreatedEvent;
use Sulu\Component\Security\Authentication\UserInterface;

class UserCreatedEventTest extends TestCase
{
    use ProphecyTrait;

    public function testGetEventType(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserCreatedEvent($user->reveal(), []);

        $this->assertSame('created', $event->getEventType());
    }

    public function testGetPayload(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserCreatedEvent($user->reveal(), ['test' => '123']);

        $this->assertSame(['test' => '123'], $event->getEventPayload());
    }

    public function testGetResourceKey(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserCreatedEvent($user->reveal(), []);

        $this->assertSame('users', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $user->getId()->shouldBeCalled()->willReturn(1);
        $event = new UserCreatedEvent($user->reveal(), []);

        $this->assertSame('1', $event->getResourceId());
    }

    public function testGetResourceSecurityContext(): void
    {
        $user = $this->prophesize(UserInterface::class);
        $event = new UserCreatedEvent($user->reveal(), []);

        $this->assertSame('sulu.security.users', $event->getResourceSecurityContext());
    }
}
