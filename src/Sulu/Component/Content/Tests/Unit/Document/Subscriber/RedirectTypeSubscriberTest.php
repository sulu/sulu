<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Subscriber;

use Prophecy\Argument;
use Sulu\Component\Content\Document\Behavior\RedirectTypeBehavior;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\Subscriber\RedirectTypeSubscriber;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Metadata;

class RedirectTypeSubscriberTest extends SubscriberTestCase
{
    /**
     * @var RedirectTypeSubscriber
     */
    private $subscriber;

    /**
     * @var RedirectTypeBehavior
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

    public function setUp()
    {
        parent::setUp();

        $this->document = $this->prophesize(RedirectTypeBehavior::class);
        $this->persistEvent->getDocument()->willReturn($this->document->reveal());
        $this->metadata = $this->prophesize(Metadata::class);
        $this->event = $this->prophesize(MetadataLoadEvent::class);
        $this->subscriber = new RedirectTypeSubscriber($this->encoder->reveal());

        $this->event->getMetadata()->willReturn($this->metadata);
    }

    public function testHandlePersist()
    {
        $this->document->setRedirectTarget(new \stdClass());
        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testHandlePersistSelf()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->document->getRedirectTarget()->willReturn($this->document->reveal());

        $this->subscriber->handlePersist($this->persistEvent->reveal());
    }

    public function testLoadMetadata()
    {
        $this->metadata->getReflectionClass()->willReturn(new \ReflectionClass($this->document->reveal()));
        $this->metadata->addFieldMapping('redirectType', Argument::any())->shouldBeCalled();
        $this->metadata->addFieldMapping('redirectExternal', Argument::any())->shouldBeCalled();
        $this->metadata->addFieldMapping('redirectTarget', Argument::any())->shouldBeCalled();
        $this->subscriber->handleMetadataLoad($this->event->reveal());
    }
}
