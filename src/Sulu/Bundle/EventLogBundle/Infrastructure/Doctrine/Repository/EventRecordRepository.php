<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Domain\Repository\EventRecordRepositoryInterface;

class EventRecordRepository implements EventRecordRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var EntityRepository<EventRecordInterface>
     */
    protected $entityRepository;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(EventRecordInterface::class);
    }

    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface
    {
        /** @var class-string<EventRecordInterface> $className */
        $className = $this->entityRepository->getClassName();

        /** @var EventRecordInterface $eventRecord */
        $eventRecord = new $className();
        $eventRecord->setEventType($domainEvent->getEventType());
        $eventRecord->setEventContext($domainEvent->getEventContext());
        $eventRecord->setEventPayload($domainEvent->getEventPayload());
        $eventRecord->setEventDateTime($domainEvent->getEventDateTime());
        $eventRecord->setEventBatch($domainEvent->getEventBatch());
        $eventRecord->setUser($domainEvent->getUser());
        $eventRecord->setResourceKey($domainEvent->getResourceKey());
        $eventRecord->setResourceId($domainEvent->getResourceId());
        $eventRecord->setResourceLocale($domainEvent->getResourceLocale());
        $eventRecord->setResourceWebspaceKey($domainEvent->getResourceWebspaceKey());
        $eventRecord->setResourceTitle($domainEvent->getResourceTitle());
        $eventRecord->setResourceTitleLocale($domainEvent->getResourceTitleLocale());
        $eventRecord->setResourceSecurityContext($domainEvent->getResourceSecurityContext());
        $eventRecord->setResourceSecurityType($domainEvent->getResourceSecurityType());

        return $eventRecord;
    }

    public function add(EventRecordInterface $eventRecord): void
    {
        $this->entityManager->persist($eventRecord);
    }

    public function commit(): void
    {
        $this->entityManager->flush();
    }
}
