<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Tests\Unit\Subscriber\Behavior\Mapping;

use PHPCR\NodeInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\DocumentManager\Behavior\Mapping\UuidBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping\UuidSubscriber;

class UuidSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<HydrateEvent>
     */
    private $hydrateEvent;

    /**
     * @var object
     */
    private $notImplementing;

    /**
     * @var ObjectProphecy<NodeInterface>
     */
    private $node;

    /**
     * @var TestUuidDocument
     */
    private $document;

    /**
     * @var DocumentAccessor
     */
    private $accessor;

    /**
     * @var UuidSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->hydrateEvent = $this->prophesize(HydrateEvent::class);
        $this->notImplementing = new \stdClass();
        $this->node = $this->prophesize(NodeInterface::class);
        $this->document = new TestUuidDocument();
        $this->accessor = new DocumentAccessor($this->document);

        $this->subscriber = new UuidSubscriber();
    }

    /**
     * It should return early when not implementing.
     */
    public function testHydrateNotImplementing(): void
    {
        $this->hydrateEvent->getDocument()->willReturn($this->notImplementing)->shouldBeCalled();
        $this->subscriber->handleUuid($this->hydrateEvent->reveal());
    }

    /**
     * It should set the node name on the document.
     */
    public function testUuid(): void
    {
        $this->hydrateEvent->getNode()->willReturn($this->node->reveal());
        $this->hydrateEvent->getDocument()->willReturn($this->document);
        $this->hydrateEvent->getAccessor()->willReturn($this->accessor);
        $this->node->getIdentifier()->willReturn('hello');

        $this->subscriber->handleUuid($this->hydrateEvent->reveal());

        $this->assertEquals('hello', $this->document->getUuid());
    }
}

class TestUuidDocument implements UuidBehavior
{
    private $uuid;

    public function getUuid()
    {
        return $this->uuid;
    }
}
