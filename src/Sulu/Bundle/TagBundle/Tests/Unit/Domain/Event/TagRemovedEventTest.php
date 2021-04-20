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
use Sulu\Bundle\TagBundle\Domain\Event\TagRemovedEvent;

class TagRemovedEventTest extends TestCase
{
    public function testGetEventType(): void
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('removed', $event->getEventType());
    }

    public function testGetEventContext(): void
    {
        $event = $this->createTagRemovedEvent(
            1234,
         'tag-name-123',
            [
                'wasMerged' => true,
                'destinationTagId' => 5566,
            ]
        );

        static::assertSame(
            [
                'wasMerged' => true,
                'destinationTagId' => 5566,
            ],
            $event->getEventContext()
        );
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('tags', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createTagRemovedEvent(5678);

        static::assertSame('5678', $event->getResourceId());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createTagRemovedEvent(1234, 'tag-name-456');

        static::assertSame('tag-name-456', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createTagRemovedEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('sulu.settings.tags', $event->getResourceSecurityContext());
    }

    /**
     * @param mixed[] $context
     */
    private function createTagRemovedEvent(
        int $tagId = 1234,
        string $tagName = 'tag-name',
        array $context = []
    ): TagRemovedEvent {
        return new TagRemovedEvent(
            $tagId,
            $tagName,
            $context
        );
    }
}
