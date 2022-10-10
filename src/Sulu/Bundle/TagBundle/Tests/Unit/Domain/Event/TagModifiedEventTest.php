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
use Sulu\Bundle\TagBundle\Domain\Event\TagModifiedEvent;
use Sulu\Bundle\TagBundle\Tag\TagInterface;

class TagModifiedEventTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<TagInterface>
     */
    private $tag;

    public function setUp(): void
    {
        $this->tag = $this->prophesize(TagInterface::class);
    }

    public function testGetTag(): void
    {
        $event = $this->createTagModifiedEvent();

        static::assertSame($this->tag->reveal(), $event->getTag());
    }

    public function testGetEventType(): void
    {
        $event = $this->createTagModifiedEvent();

        static::assertSame('modified', $event->getEventType());
    }

    public function testGetEventPayload(): void
    {
        $event = $this->createTagModifiedEvent(['name' => 'test-name']);

        static::assertSame(['name' => 'test-name'], $event->getEventPayload());
    }

    public function testGetResourceKey(): void
    {
        $event = $this->createTagModifiedEvent();

        static::assertSame('tags', $event->getResourceKey());
    }

    public function testGetResourceId(): void
    {
        $event = $this->createTagModifiedEvent();
        $this->tag->getId()->willReturn(1234);

        static::assertSame('1234', $event->getResourceId());
    }

    public function testGetResourceTitle(): void
    {
        $event = $this->createTagModifiedEvent();
        $this->tag->getName()->willReturn('tag-name');

        static::assertSame('tag-name', $event->getResourceTitle());
    }

    public function testGetResourceTitleLocale(): void
    {
        $event = $this->createTagModifiedEvent();

        static::assertNull($event->getResourceTitleLocale());
    }

    public function testGetResourceSecurityContext(): void
    {
        $event = $this->createTagModifiedEvent();

        static::assertSame('sulu.settings.tags', $event->getResourceSecurityContext());
    }

    /**
     * @param mixed[] $payload
     */
    private function createTagModifiedEvent(
        array $payload = []
    ): TagModifiedEvent {
        return new TagModifiedEvent(
            $this->tag->reveal(),
            $payload
        );
    }
}
