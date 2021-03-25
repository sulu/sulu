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
use Sulu\Bundle\CustomUrlBundle\Domain\Event\CustomUrlRemovedEvent;

class CustomUrlRemovedEventTest extends TestCase
{
    public function testGetEventType()
    {
        $event = $this->createCustomUrlRemovedEvent();

        static::assertSame('removed', $event->getEventType());
    }

    public function testGetResourceKey()
    {
        $event = $this->createCustomUrlRemovedEvent();

        static::assertSame('custom_urls', $event->getResourceKey());
    }

    public function testGetResourceId()
    {
        $event = $this->createCustomUrlRemovedEvent('custom-url-1234-1234');

        static::assertSame('custom-url-1234-1234', $event->getResourceId());
    }

    public function testGetResourceWebspaceKey()
    {
        $event = $this->createCustomUrlRemovedEvent(
            'custom-url-id-1234-1234',
            'custom-url-title',
            'test-io'
        );

        static::assertSame('test-io', $event->getResourceWebspaceKey());
    }

    public function testGetResourceTitle()
    {
        $event = $this->createCustomUrlRemovedEvent(
            'custom-url-id-1234-1234',
            'custom-url-title-123'
        );

        static::assertSame('custom-url-title-123', $event->getResourceTitle());
    }

    public function testGetResourceSecurityContext()
    {
        $event = $this->createCustomUrlRemovedEvent(
            'custom-url-id-1234-1234',
            'custom-url-title',
            'test-io'
        );

        static::assertSame('sulu.webspaces.test-io.custom-urls', $event->getResourceSecurityContext());
    }

    private function createCustomUrlRemovedEvent(
        string $customUrlUuid = 'custom-url-id-1234-1234',
        string $customUrlTitle = 'custom-url-title',
        string $webspaceKey = 'sulu-io'
    ): CustomUrlRemovedEvent {
        return new CustomUrlRemovedEvent(
            $customUrlUuid,
            $customUrlTitle,
            $webspaceKey
        );
    }
}
