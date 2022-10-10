<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Infrastructure\Doctrine\Subscriber;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Subscriber\DomainEventCollectorSubscriber;

class DomainEventCollectorSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<DomainEventCollectorInterface>
     */
    private $domainEventCollector;

    public function setUp(): void
    {
        $this->domainEventCollector = $this->prophesize(DomainEventCollectorInterface::class);
    }

    public function testOnClear(): void
    {
        $subscriber = $this->createDomainEventCollectorSubscriber();

        $this->domainEventCollector->clear()->shouldBeCalled();
        $this->domainEventCollector->dispatch()->shouldNotBeCalled();

        $onClearEventArgs = $this->prophesize(OnClearEventArgs::class);
        $subscriber->onClear($onClearEventArgs->reveal());
    }

    public function testPostFlush(): void
    {
        $subscriber = $this->createDomainEventCollectorSubscriber();

        $this->domainEventCollector->clear()->shouldNotBeCalled();
        $this->domainEventCollector->dispatch()->shouldBeCalled();

        $postFlushEvent = $this->prophesize(PostFlushEventArgs::class);
        $subscriber->postFlush($postFlushEvent->reveal());
    }

    private function createDomainEventCollectorSubscriber(): DomainEventCollectorSubscriber
    {
        return new DomainEventCollectorSubscriber(
            $this->domainEventCollector->reveal()
        );
    }
}
