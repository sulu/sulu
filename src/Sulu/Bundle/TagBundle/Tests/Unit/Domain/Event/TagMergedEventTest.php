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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\TagBundle\Domain\Event\TagMergedEvent;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagMergedEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TagInterface>
     */
    private $destinationTag;

    public function setUp(): void
    {
        $this->destinationTag = $this->prophesize(TagInterface::class);
    }

    public function testGetDestinationTag(): void
    {
        $event = $this->createTagMergedEvent();

        static::assertSame($this->destinationTag->reveal(), $event->getDestinationTag());
    }

    public function testGetEventType(): void
    {
        $event = $this->createTagMergedEvent();

        static::assertSame('merged', $event->getEventType());
    }

    public function testGetEventContext(): void
    {
        $event = $this->createTagMergedEvent(5678, 'source-tag-123');

        static::assertSame(['sourceTagId' => 5678, 'sourceTagName' => 'source-tag-123'], $event->getEventContext());
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createTagMergedEvent();

        static::assertSame('tags', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createTagMergedEvent();
        $this->destinationTag->getId()->willReturn(1234);

        static::assertSame('1234', $event->getResourceId());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createTagMergedEvent();
        $this->destinationTag->getName()->willReturn('tag-name');

        static::assertSame('tag-name', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createTagMergedEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createTagMergedEvent();

        static::assertSame('sulu.settings.tags', $event->getResourceSecurityContext());
    }

    private function createTagMergedEvent(
        int $sourceTagId = 1234,
        string $sourceTagName = 'source-tag-name'
    ): TagMergedEvent {
        return new TagMergedEvent(
            $this->destinationTag->reveal(),
            $sourceTagId,
            $sourceTagName
        );
    }
}
