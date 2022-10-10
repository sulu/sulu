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
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRouteRemovedEvent;
use Sulu\Component\CustomUrl\Document\CustomUrlDocument;

class CustomUrlRouteRemovedEventTest extends TestCase
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
        $event = $this->createCustomUrlRouteRemovedEvent();

        static::assertSame($this->customUrlDocument->reveal(), $event->getCustomUrlDocument());
    }

    public function testGetEventType(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent();

        static::assertSame('route_removed', $event->getEventType());
    }

    public function testGetEventContext(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent('sulu-io', 'route-1234-1234');

        static::assertSame(['routeUuid' => 'route-1234-1234'], $event->getEventContext());
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent();

        static::assertSame('custom_urls', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent();
        $this->customUrlDocument->getUuid()->willReturn('1234-1234-1234-1234');

        static::assertSame('1234-1234-1234-1234', $event->getResourceId());
    }

    public function testGetResourceWebspaceKey(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent('test-io');

        static::assertSame('test-io', $event->getResourceWebspaceKey());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent();
        $this->customUrlDocument->getTitle()->willReturn('custom-url-title');

        static::assertSame('custom-url-title', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createCustomUrlRouteRemovedEvent('test-io');

        static::assertSame('sulu.webspaces.test-io.custom-urls', $event->getResourceSecurityContext());
    }

    private function createCustomUrlRouteRemovedEvent(
        string $webspaceKey = 'sulu-io',
        string $routeUuid = 'route-id-1234-1234'
    ): CustomUrlRouteRemovedEvent {
        return new CustomUrlRouteRemovedEvent(
            $this->customUrlDocument->reveal(),
            $webspaceKey,
            $routeUuid
        );
    }
}
