<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\SegmentBehavior;
use Sulu\Component\Content\Document\Subscriber\SegmentSubscriber;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Metadata;

class SegmentSubscriberTest extends TestCase
{
    /**
     * @var NavigationContextSubscriber
     */
    private $subscriber;

    /**
     * @var NavigationContextBehavior
     */
    private $document;

    /**
     * @var Metadata
     */
    private $metadata;

    /**
     * @var MetadataLoadEvent
     */
    private $event;

    public function setUp(): void
    {
        $this->document = $this->prophesize(SegmentBehavior::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->event = $this->prophesize(MetadataLoadEvent::class);
        $this->subscriber = new SegmentSubscriber();

        $this->event->getMetadata()->willReturn($this->metadata);
    }

    public function testLoadMetadata()
    {
        $this->metadata->getReflectionClass()->willReturn(new \ReflectionClass($this->document->reveal()));
        $this->metadata->addFieldMapping('segment', Argument::any())->shouldBeCalled();
        $this->subscriber->handleMetadataLoad($this->event->reveal());
    }
}
