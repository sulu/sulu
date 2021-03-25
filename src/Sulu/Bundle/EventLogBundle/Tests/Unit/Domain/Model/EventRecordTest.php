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
    public function testEventType()
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setEventType('created'));
        static::assertSame('created', $event->getEventType());
    }

    public function testEventContext()
    {
        $event = $this->createEventRecord();

        static::assertSame([], $event->getEventContext());
        static::assertSame($event, $event->setEventContext(['relatedPageId' => 'page-123']));
        static::assertSame(['relatedPageId' => 'page-123'], $event->getEventContext());
    }

    public function testEventPayload()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getEventPayload());
        static::assertSame($event, $event->setEventPayload(['name' => 'name-123', 'description' => 'description-123']));
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $event->getEventPayload());
    }

    public function testEventDateTime()
    {
        $event = $this->createEventRecord();
        $dateTime = new \DateTimeImmutable('2020-01-01');

        static::assertSame($event, $event->setEventDateTime($dateTime));
        static::assertSame($dateTime, $event->getEventDateTime());
    }

    public function testEventBatch()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getEventBatch());
        static::assertSame($event, $event->setEventBatch('batch-1234'));
        static::assertSame('batch-1234', $event->getEventBatch());
    }

    public function testUser()
    {
        $event = $this->createEventRecord();
        $user = $this->prophesize(UserInterface::class);

        static::assertNull($event->getUser());
        static::assertSame($event, $event->setUser($user->reveal()));
        static::assertSame($user->reveal(), $event->getUser());
    }

    public function testResourceKey()
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setResourceKey('pages'));
        static::assertSame('pages', $event->getResourceKey());
    }

    public function testResourceId()
    {
        $event = $this->createEventRecord();

        static::assertSame($event, $event->setResourceId('1234-1234-1234-1234'));
        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testResourceLocale()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceLocale());
        static::assertSame($event, $event->setResourceLocale('en'));
        static::assertSame('en', $event->getResourceLocale());
    }

    public function testResourceWebspaceKey()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceWebspaceKey());
        static::assertSame($event, $event->setResourceWebspaceKey('sulu-io'));
        static::assertSame('sulu-io', $event->getResourceWebspaceKey());
    }

    public function testResourceTitle()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceTitle());
        static::assertSame($event, $event->setResourceTitle('title-1234'));
        static::assertSame('title-1234', $event->getResourceTitle());
    }

    public function testResourceSecurityContext()
    {
        $event = $this->createEventRecord();

        static::assertNull($event->getResourceSecurityContext());
        static::assertSame($event, $event->setResourceSecurityContext('sulu.webspaces.sulu-io'));
        static::assertSame('sulu.webspaces.sulu-io', $event->getResourceSecurityContext());
    }

    public function testResourceSecurityType()
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
