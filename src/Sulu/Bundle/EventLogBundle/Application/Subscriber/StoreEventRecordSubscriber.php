<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Application\Subscriber;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Repository\EventRecordRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StoreEventRecordSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventRecordRepositoryInterface
     */
    private $eventRecordRepository;

    public function __construct(
        EventRecordRepositoryInterface $eventRecordRepository
    ) {
        $this->eventRecordRepository = $eventRecordRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            DomainEvent::class => ['storeEventRecord', -256],
        ];
    }

    public function storeEventRecord(DomainEvent $event)
    {
        $eventRecord = $this->eventRecordRepository->createForDomainEvent($event);
        $this->eventRecordRepository->add($eventRecord);
        $this->eventRecordRepository->commit();
    }
}
