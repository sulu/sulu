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

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Domain\Repository\EventRecordRepositoryInterface;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class EventRecordRepository extends EntityRepository implements EventRecordRepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface
    {
        /** @var EventRecordInterface $eventRecord */
        $eventRecord = $this->createNew();

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
        $eventRecord->setResourceSecurityContext($domainEvent->getResourceSecurityContext());
        $eventRecord->setResourceSecurityType($domainEvent->getResourceSecurityType());

        return $eventRecord;
    }

    public function add(EventRecordInterface $eventRecord): void
    {
        $this->getEntityManager()->persist($eventRecord);
    }

    public function commit(): void
    {
        $this->getEntityManager()->flush();
    }
}
