<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CustomUrlBundle\Tests\Unit\Domain\Event;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRestoredEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRestoredEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<CustomUrlDocument>
     */
    private $customUrlDocument;

    public function setUp(): void
    {
        $this->customUrlDocument = $this->prophesize(CustomUrlDocument::class);
    }

    public function testGetCustomUrlDocument(): void
    {
        $event = $this->createCustomUrlRestoredEvent();

        static::assertSame($this->customUrlDocument->reveal(), $event->getCustomUrlDocument());
    }

    public function testGetEventType(): void
    {
        $event = $this->createCustomUrlRestoredEvent();

        static::assertSame('restored', $event->getEventType());
    }

    public function testGetEventPayload(): void
    {
        $event = $this->createCustomUrlRestoredEvent('sulu-io', ['name' => 'test-name']);

        static::assertSame(['name' => 'test-name'], $event->getEventPayload());
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createCustomUrlRestoredEvent();

        static::assertSame('custom_urls', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createCustomUrlRestoredEvent();
        $this->customUrlDocument->getUuid()->willReturn('1234-1234-1234-1234');

        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testGetResourceWebspaceKey(): void
    {
        $event = $this->createCustomUrlRestoredEvent('test-io');

        static::assertSame('test-io', $event->getResourceWebspaceKey());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createCustomUrlRestoredEvent();
        $this->customUrlDocument->getTitle()->willReturn('custom-url-title');

        static::assertSame('custom-url-title', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createCustomUrlRestoredEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createCustomUrlRestoredEvent('test-io');

        static::assertSame('sulu.webspaces.test-io.custom-urls', $event->getResourceSecurityContext());
    }

    /**
     * @param mixed[] $payload
     */
    private function createCustomUrlRestoredEvent(
        string $webspaceKey = 'sulu-io',
        array $payload = []
    ): CustomUrlRestoredEvent {
        return new CustomUrlRestoredEvent(
            $this->customUrlDocument->reveal(),
            $webspaceKey,
            $payload
        );
    }
}
