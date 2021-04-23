<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\EventLogBundle\Tests\Application\Domain\Event\TestDomainEvent;
use Sulu\Component\Security\Authentication\UserInterface;

class DomainEventTest extends TestCase
{
    public function testEventType(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getEventType());
    }

    public function testEventContext(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertSame([], $event->getEventContext());
    }

    public function testEventPayload(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getEventPayload());
    }

    public function testEventDateTime(): void
    {
        $event = $this->createTestDomainEvent();
        $dateTime = new \DateTimeImmutable('2020-01-01');

        static::assertEqualsWithDelta(new \DateTimeImmutable('now'), $event->getEventDateTime(), 10);
        static::assertSame($event, $event->setEventDateTime($dateTime));
        static::assertSame($dateTime, $event->getEventDateTime());
    }

    public function testEventBatch(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getEventBatch());
        static::assertSame($event, $event->setEventBatch('batch-1234'));
        static::assertSame('batch-1234', $event->getEventBatch());
    }

    public function testUser(): void
    {
        $event = $this->createTestDomainEvent();
        $user = $this->prophesize(UserInterface::class);

        static::assertNull($event->getUser());
        static::assertSame($event, $event->setUser($user->reveal()));
        static::assertNotNull($event->getUser());
        static::assertSame($user->reveal(), $event->getUser());
    }

    public function testResourceKey(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getResourceKey());
    }

    public function testResourceId(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getResourceId());
    }

    public function testResourceLocale(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceLocale());
    }

    public function testResourceWebspaceKey(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceWebspaceKey());
    }

    public function testResourceTitle(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceTitle());
    }

    public function testResourceTitleLocale(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testResourceSecurityContext(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceSecurityContext());
    }

    public function testResourceSecurityType(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertNull($event->getResourceSecurityType());
    }

    public function testResourceSecurityObjectId(): void
    {
        $event = $this->createTestDomainEvent();

        static::assertSame('test', $event->getResourceSecurityObjectId());
    }

    private function createTestDomainEvent(): TestDomainEvent
    {
        return new TestDomainEvent();
    }
}
