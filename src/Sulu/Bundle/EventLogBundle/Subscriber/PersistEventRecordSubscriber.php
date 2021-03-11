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
use Sulu\Bundle\EventLogBundle\Entity\EventRecord;
use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersistEventRecordSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            DomainEvent::class => ['persistsEventRecord', -256],
        ];
    }

    public function persistsEventRecord(DomainEvent $event)
    {
        $eventRecord = new EventRecord();
        $eventRecord->setEventType($event->getEventType());
        $eventRecord->setEventPayload($event->getEventPayload());
        $eventRecord->setEventDateTime($event->getEventDateTime());
        $eventRecord->setEventBatch($event->getEventBatch());
        $eventRecord->setUser($event->getUser());
        $eventRecord->setResourceKey($event->getResourceKey());
        $eventRecord->setResourceId($event->getResourceId());
        $eventRecord->setResourceLocale($event->getResourceLocale());
        $eventRecord->setResourceTitle($event->getResourceTitle());
        $eventRecord->setResourceSecurityContext($event->getResourceSecurityContext());
        $eventRecord->setResourceSecurityType($event->getResourceSecurityType());

        $this->entityManager->persist($eventRecord);
        $this->entityManager->flush();
    }
}
