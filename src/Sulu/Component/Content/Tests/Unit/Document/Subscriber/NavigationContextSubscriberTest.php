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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Component\Content\Document\Behavior\NavigationContextBehavior;
use Sulu\Component\Content\Document\Subscriber\NavigationContextSubscriber;
use Sulu\Component\DocumentManager\Event\MetadataLoadEvent;
use Sulu\Component\DocumentManager\Metadata;

class NavigationContextSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var NavigationContextSubscriber
     */
    private $subscriber;

    /**
     * @var ObjectProphecy<NavigationContextBehavior>
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
        $this->document = $this->prophesize(NavigationContextBehavior::class);
        $this->metadata = $this->prophesize(Metadata::class);
        $this->event = $this->prophesize(MetadataLoadEvent::class);
        $this->subscriber = new NavigationContextSubscriber();

        $this->event->getMetadata()->willReturn($this->metadata);
    }

    public function testLoadMetadata(): void
    {
        $this->metadata->getReflectionClass()->willReturn(new \ReflectionClass($this->document->reveal()));
        $this->metadata->addFieldMapping('navigationContexts', Argument::any())->shouldBeCalled();
        $this->subscriber->handleMetadataLoad($this->event->reveal());
    }
}
