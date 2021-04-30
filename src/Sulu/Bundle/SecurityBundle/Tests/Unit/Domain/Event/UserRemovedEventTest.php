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
use Sulu\Bundle\SecurityBundle\Domain\Event\UserRemovedEvent;

class UserRemovedEventTest extends TestCase
{
    public function testGetEventType(): void
    {
        $event = new UserRemovedEvent(1);

        $this->assertSame($event->getEventType(), 'removed');
    }

    public function testGetResourceKey(): void
    {
        $event = new UserRemovedEvent(1);

        $this->assertSame('users', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = new UserRemovedEvent(1);

        $this->assertSame('1', $event->getResourceId());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = new UserRemovedEvent(1);

        $this->assertSame('sulu.security.users', $event->getResourceSecurityContext());
    }
}
