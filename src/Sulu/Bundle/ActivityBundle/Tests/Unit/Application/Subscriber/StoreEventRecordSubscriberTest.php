<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Unit\Application\Subscriber;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Application\Subscriber\StoreEventRecordSubscriber;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\ActivityBundle\Domain\Repository\EventRecordRepositoryInterface;

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

    public function testStoreEventRecord(): void
    {
        $subscriber = $this->createStoreEventRecordSubscriber();

        $event = $this->prophesize(DomainEvent::class);
        $eventRecord = $this->prophesize(EventRecordInterface::class);

        $this->eventRecordRepository->createForDomainEvent($event->reveal())
            ->willReturn($eventRecord->reveal());
        $this->eventRecordRepository->addAndCommit($eventRecord->reveal())->shouldBeCalled();

        $subscriber->storeEventRecord($event->reveal());
    }

    private function createStoreEventRecordSubscriber(): StoreEventRecordSubscriber
    {
        return new StoreEventRecordSubscriber(
            $this->eventRecordRepository->reveal()
        );
    }
}
