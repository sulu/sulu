<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Application\Subscriber\StoreEventRecordSubscriber;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Domain\Repository\EventRecordRepositoryInterface;

class StoreEventRecordSubscriberTest extends TestCase
{
    /**
     * @var EventRecordRepositoryInterface|ObjectProphecy
     */
    private $eventRecordRepository;

    public function setUp(): void
    {
        $this->eventRecordRepository = $this->prophesize(EventRecordRepositoryInterface::class);
    }

    public function testStoreEventRecord()
    {
        $subscriber = $this->createStoreEventRecordSubscriber();

        $event = $this->prophesize(DomainEvent::class);
        $eventRecord = $this->prophesize(EventRecordInterface::class);

        $this->eventRecordRepository->createForDomainEvent($event->reveal())
            ->willReturn($eventRecord->reveal());
        $this->eventRecordRepository->add($eventRecord->reveal())->shouldBeCalled();
        $this->eventRecordRepository->commit()->shouldBeCalled();

        $subscriber->storeEventRecord($event->reveal());
    }

    private function createStoreEventRecordSubscriber(): StoreEventRecordSubscriber
    {
        return new StoreEventRecordSubscriber(
            $this->eventRecordRepository->reveal()
        );
    }
}
