<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\EventLogBundle\Tests\Application\Domain\Event\TestDomainEvent;
use Sulu\Component\Security\Authentication\UserInterface;

class DomainEventTest extends TestCase
{
    public function testEventType()
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getEventType());
    }

    public function testEventContext()
    {
        $event = $this->createTestDomainEvent();

        static::assertSame([], $event->getEventContext());
    }

    public function testEventPayload()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getEventPayload());
    }

    public function testEventDateTime()
    {
        $event = $this->createTestDomainEvent();
        $dateTime = new \DateTimeImmutable('2020-01-01');

        static::assertEqualsWithDelta(new \DateTimeImmutable('now'), $event->getEventDateTime(), 10);
        static::assertSame($event, $event->setEventDateTime($dateTime));
        static::assertSame($dateTime, $event->getEventDateTime());
    }

    public function testEventBatch()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getEventBatch());
        static::assertSame($event, $event->setEventBatch('batch-1234'));
        static::assertSame('batch-1234', $event->getEventBatch());
    }

    public function testUser()
    {
        $event = $this->createTestDomainEvent();
        $user = $this->prophesize(UserInterface::class);

        static::assertNull($event->getUser());
        static::assertSame($event, $event->setUser($user->reveal()));
        static::assertSame($user->reveal(), $event->getUser());
    }

    public function testResourceKey()
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getResourceKey());
    }

    public function testResourceId()
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getResourceId());
    }

    public function testResourceLocale()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceLocale());
    }

    public function testResourceWebspaceKey()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceWebspaceKey());
    }

    public function testResourceTitle()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceTitle());
    }

    public function testResourceSecurityContext()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceSecurityContext());
    }

    public function testResourceSecurityType()
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceSecurityType());
    }

    private function createTestDomainEvent(): TestDomainEvent
    {
        return new TestDomainEvent();
    }
}
