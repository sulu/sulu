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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\Subscriber\RedirectTypeSubscriber;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Metadata;

class RedirectTypeSubscriberTest extends SubscriberTestCase
{
    use ProphecyTrait;

    /**
     * @var RedirectTypeSubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<RedirectTypeBehavior>
     */
    private $document;

    /**
     * @var ObjectProphecy<Metadata>
     */
    private $metadata;

    /**
     * @var ObjectProphecy<MetadataLoadEvent>
     */
    private $event;

    public function setUp(): void
    {
        parent::setUp();

        $this->document = $this->prophesize(RedirectTypeBehavior::class);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->metadata = $this->prophesize(Metadata::class);
        $this->event = $this->prophesize(MetadataLoadEvent::class);
        $this->subscriber = new RedirectTypeSubscriber($this->encoder->reveal());

        $this->event->getMetadata()->willReturn($this->metadata);
    }

    public function testHandlePersist(): void
    {
        $this->document->setRedirectTarget(new \stdClass());
        $this->document->getRedirectTarget()->shouldBeCalled();
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePersistSelf(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->document->getRedirectTarget()->willReturn($this->document->reveal())->shouldBeCalled();

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testLoadMetadata(): void
    {
        $this->metadata->getReflectionClass()->willReturn(new \ReflectionClass($this->document->reveal()));
        $this->metadata->addFieldMapping('redirectType', Argument::any())->shouldBeCalled();
        $this->metadata->addFieldMapping('redirectExternal', Argument::any())->shouldBeCalled();
        $this->metadata->addFieldMapping('redirectTarget', Argument::any())->shouldBeCalled();
        $this->subscriber->handleMetadataLoad($this->event->reveal());
    }
}
