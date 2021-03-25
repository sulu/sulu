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
    public function testGetEventType()
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('removed', $event->getEventType());
    }

    public function testGetEventContext()
    {
        $event = $this->createTagRemovedEvent(
            1234,
         'tag-name-123',
         true
        );

        static::assertSame(['tagWasMerged' => true], $event->getEventContext());
    }

    public function testGetResourceKey()
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('tags', $event->getResourceKey());
    }

    public function testGetResourceId()
    {
        $event = $this->createTagRemovedEvent(5678);

        static::assertSame('5678', $event->getResourceId());
    }

    public function testGetResourceTitle()
    {
        $event = $this->createTagRemovedEvent(1234, 'tag-name-456');

        static::assertSame('tag-name-456', $event->getResourceTitle());
    }

    public function testGetResourceSecurityContext()
    {
        $event = $this->createTagRemovedEvent();

        static::assertSame('sulu.settings.tags', $event->getResourceSecurityContext());
    }

    private function createTagRemovedEvent(
        int $tagId = 1234,
        string $tagName = 'tag-name',
        bool $tagWasMerged = false
    ): TagRemovedEvent {
        return new TagRemovedEvent(
            $tagId,
            $tagName,
            $tagWasMerged
        );
    }
}
