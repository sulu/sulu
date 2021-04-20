<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Unit\Model\Event;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecord;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Security\Authentication\UserInterface;

class EventRecordTest extends TestCase
{
    public function testEventType(): void
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setEventType('created'));
        static::assertSame('created', $event->getEventType());
    }

    public function testEventContext(): void
    {
        $event = $this->createEventRecord();

        static::assertSame([], $event->getEventContext());
        static::assertSame($event, $event->setEventContext(['relatedPageId' => 'page-123']));
        static::assertSame(['relatedPageId' => 'page-123'], $event->getEventContext());
    }

    public function testEventPayload(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getEventPayload());
        static::assertSame($event, $event->setEventPayload(['name' => 'name-123', 'description' => 'description-123']));
        static::assertNotNull($event->getEventPayload());
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $event->getEventPayload());
    }

    public function testEventDateTime(): void
    {
        $event = $this->createEventRecord();
        $dateTime = new \DateTimeImmutable('2020-01-01');

        static::assertSame($event, $event->setEventDateTime($dateTime));
        static::assertSame($dateTime, $event->getEventDateTime());
    }

    public function testEventBatch(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getEventBatch());
        static::assertSame($event, $event->setEventBatch('batch-1234'));
        static::assertSame('batch-1234', $event->getEventBatch());
    }

    public function testUser(): void
    {
        $event = $this->createEventRecord();
        $user = $this->prophesize(UserInterface::class);

        static::assertNull($event->getUser());
        static::assertSame($event, $event->setUser($user->reveal()));
        static::assertNotNull($event->getUser());
        static::assertSame($user->reveal(), $event->getUser());
    }

    public function testResourceKey(): void
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setResourceKey('pages'));
        static::assertSame('pages', $event->getResourceKey());
    }

    public function testResourceId(): void
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setResourceId('1234-1234-1234-1234'));
        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testResourceLocale(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceLocale());
        static::assertSame($event, $event->setResourceLocale('en'));
        static::assertSame('en', $event->getResourceLocale());
    }

    public function testResourceWebspaceKey(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceWebspaceKey());
        static::assertSame($event, $event->setResourceWebspaceKey('sulu-io'));
        static::assertSame('sulu-io', $event->getResourceWebspaceKey());
    }

    public function testResourceTitle(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceTitle());
        static::assertSame($event, $event->setResourceTitle('title-1234'));
        static::assertSame('title-1234', $event->getResourceTitle());
    }

    public function testResourceTitleLocale(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceTitleLocale());
        static::assertSame($event, $event->setResourceTitleLocale('en'));
        static::assertSame('en', $event->getResourceTitleLocale());
    }

    public function testResourceSecurityContext(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceSecurityContext());
        static::assertSame($event, $event->setResourceSecurityContext('sulu.webspaces.sulu-io'));
        static::assertSame('sulu.webspaces.sulu-io', $event->getResourceSecurityContext());
    }

    public function testResourceSecurityType(): void
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceSecurityType());
        static::assertSame($event, $event->setResourceSecurityType(SecurityBehavior::class));
        static::assertSame(SecurityBehavior::class, $event->getResourceSecurityType());
    }

    private function createEventRecord(): EventRecord
    {
        return new EventRecord();
    }
}
