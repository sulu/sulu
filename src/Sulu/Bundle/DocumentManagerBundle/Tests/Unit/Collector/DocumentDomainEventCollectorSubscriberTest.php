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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorInterface;
use Sulu\Bundle\DocumentManagerBundle\Collector\DocumentDomainEventCollectorSubscriber;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;

class DocumentDomainEventCollectorSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DocumentDomainEventCollectorInterface>
     */
    private $documentDomainEventCollector;

    public function setUp(): void
    {
        $this->documentDomainEventCollector = $this->prophesize(DocumentDomainEventCollectorInterface::class);
    }

    public function testOnClear(): void
    {
        $subscriber = $this->createDocumentDomainEventCollectorSubscriber();

        $this->documentDomainEventCollector->clear()->shouldBeCalled();
        $this->documentDomainEventCollector->dispatch()->shouldNotBeCalled();

        $clearEvent = $this->prophesize(ClearEvent::class);
        $subscriber->onClear($clearEvent->reveal());
    }

    public function testPostFlush(): void
    {
        $subscriber = $this->createDocumentDomainEventCollectorSubscriber();

        $this->documentDomainEventCollector->clear()->shouldNotBeCalled();
        $this->documentDomainEventCollector->dispatch()->shouldBeCalled();

        $flushEvent = $this->prophesize(FlushEvent::class);
        $subscriber->onFlush($flushEvent->reveal());
    }

    private function createDocumentDomainEventCollectorSubscriber(): DocumentDomainEventCollectorSubscriber
    {
        return new DocumentDomainEventCollectorSubscriber(
            $this->documentDomainEventCollector->reveal()
        );
    }
}
