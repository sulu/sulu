<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Model\Event;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\ActivityBundle\Domain\Model\Activity;
use Sulu\Component\Security\Authentication\UserInterface;

class ActivityTest extends TestCase
{
    use ProphecyTrait;

    public function testType(): void
    {
        $event = $this->createActivity();

        static::assertSame($event, $event->setType('created'));
        static::assertSame('created', $event->getType());
    }

    public function testContext(): void
    {
        $event = $this->createActivity();

        static::assertSame([], $event->getContext());
        static::assertSame($event, $event->setContext(['relatedPageId' => 'page-123']));
        static::assertSame(['relatedPageId' => 'page-123'], $event->getContext());
    }

    public function testPayload(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getPayload());
        static::assertSame($event, $event->setPayload(['name' => 'name-123', 'description' => 'description-123']));
        static::assertNotNull($event->getPayload());
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $event->getPayload());
    }

    public function testTimestamp(): void
    {
        $event = $this->createActivity();
        $dateTime = new \DateTimeImmutable('2020-01-01');

        static::assertSame($event, $event->setTimestamp($dateTime));
        static::assertSame($dateTime, $event->getTimestamp());
    }

    public function testBatch(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getBatch());
        static::assertSame($event, $event->setBatch('batch-1234'));
        static::assertSame('batch-1234', $event->getBatch());
    }

    public function testUser(): void
    {
        $event = $this->createActivity();
        $user = $this->prophesize(UserInterface::class);

        static::assertNull($event->getUser());
        static::assertSame($event, $event->setUser($user->reveal()));
        static::assertNotNull($event->getUser());
        static::assertSame($user->reveal(), $event->getUser());
    }

    public function testResourceKey(): void
    {
        $event = $this->createActivity();

        static::assertSame($event, $event->setResourceKey('pages'));
        static::assertSame('pages', $event->getResourceKey());
    }

    public function testResourceId(): void
    {
        $event = $this->createActivity();

        static::assertSame($event, $event->setResourceId('1234-1234-1234-1234'));
        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testResourceLocale(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceLocale());
        static::assertSame($event, $event->setResourceLocale('en'));
        static::assertSame('en', $event->getResourceLocale());
    }

    public function testResourceWebspaceKey(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceWebspaceKey());
        static::assertSame($event, $event->setResourceWebspaceKey('sulu-io'));
        static::assertSame('sulu-io', $event->getResourceWebspaceKey());
    }

    public function testResourceTitle(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceTitle());
        static::assertSame($event, $event->setResourceTitle('title-1234'));
        static::assertSame('title-1234', $event->getResourceTitle());
    }

    public function testResourceTitleLocale(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceTitleLocale());
        static::assertSame($event, $event->setResourceTitleLocale('en'));
        static::assertSame('en', $event->getResourceTitleLocale());
    }

    public function testResourceSecurityContext(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceSecurityContext());
        static::assertSame($event, $event->setResourceSecurityContext('sulu.webspaces.sulu-io'));
        static::assertSame('sulu.webspaces.sulu-io', $event->getResourceSecurityContext());
    }

    public function testResourceSecurityObjectType(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceSecurityObjectType());
        static::assertSame($event, $event->setResourceSecurityObjectType('pages'));
        static::assertSame('pages', $event->getResourceSecurityObjectType());
    }

    public function testResourceSecurityObjectId(): void
    {
        $event = $this->createActivity();

        static::assertNull($event->getResourceSecurityObjectId());
        static::assertSame($event, $event->setResourceSecurityObjectId('1'));
        static::assertSame('1', $event->getResourceSecurityObjectId());
    }

    private function createActivity(): Activity
    {
        return new Activity();
    }
}
