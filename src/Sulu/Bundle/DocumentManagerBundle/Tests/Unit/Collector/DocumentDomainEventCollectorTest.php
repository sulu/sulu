<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Application\Dispatcher;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollector;
use Sulu\Bundle\EventLogBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;

class DocumentDomainEventCollectorTest extends TestCase
{
    /**
     * @var DomainEventDispatcherInterface|ObjectProphecy
     */
    private $domainEventDispatcher;

    public function setUp(): void
    {
        $this->domainEventDispatcher = $this->prophesize(DomainEventDispatcherInterface::class);
    }

    public function testCollectAndFlush()
    {
        $collector = $this->createDocumentDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $event3 = $this->prophesize(DomainEvent::class);
        $collector->collect($event3->reveal());

        $event1->getEventBatch()->willReturn(null);
        $event1->setEventBatch(Argument::type('string'))->shouldBeCalled();
        $this->domainEventDispatcher->dispatch($event1->reveal())->shouldBeCalled();

        $event2->getEventBatch()->willReturn(null);
        $event2->setEventBatch(Argument::type('string'))->shouldBeCalled();
        $this->domainEventDispatcher->dispatch($event2->reveal())->shouldBeCalled();

        $event3->getEventBatch()->willReturn('batch-1234');
        $event3->setEventBatch(Argument::cetera())->shouldNotBeCalled();
        $this->domainEventDispatcher->dispatch($event3->reveal())->shouldBeCalled();

        $flushEvent = $this->prophesize(FlushEvent::class);
        $collector->onFlush($flushEvent->reveal());
    }

    public function testCollectWithFlushAfterClear()
    {
        $collector = $this->createDocumentDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $clearEvent = $this->prophesize(ClearEvent::class);
        $collector->onClear($clearEvent->reveal());

        $flushEvent = $this->prophesize(FlushEvent::class);
        $collector->onFlush($flushEvent->reveal());
    }

    public function testCollectWithoutFlush()
    {
        $collector = $this->createDocumentDomainEventCollector();

        $event1 = $this->prophesize(DomainEvent::class);
        $collector->collect($event1->reveal());

        $event2 = $this->prophesize(DomainEvent::class);
        $collector->collect($event2->reveal());

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();
    }

    public function testFlushWithoutCollect()
    {
        $collector = $this->createDocumentDomainEventCollector();

        $this->domainEventDispatcher->dispatch(Argument::cetera())->shouldNotBeCalled();

        $flushEvent = $this->prophesize(FlushEvent::class);
        $collector->onFlush($flushEvent->reveal());
    }

    private function createDocumentDomainEventCollector(): DocumentDomainEventCollector
    {
        return new DocumentDomainEventCollector(
            $this->domainEventDispatcher->reveal()
        );
    }
}
