<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\EventLogBundle\Entity\EventRecordRepositoryInterface;
use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StoreEventRecordSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventRecordRepositoryInterface
     */
    private $eventRecordRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EventRecordRepositoryInterface $eventRecordRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->eventRecordRepository = $eventRecordRepository;
        $this->entityManager = $entityManager;
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
