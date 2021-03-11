<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Entity;

use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Sulu\Component\Persistence\Repository\ORM\EntityRepository;

class EventRecordRepository extends EntityRepository implements EventRecordRepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface
    {
        $eventRecord = $this->createNew();

        $eventRecord->setEventType($domainEvent->getEventType());
        $eventRecord->setEventPayload($domainEvent->getEventPayload());
        $eventRecord->setEventDateTime($domainEvent->getEventDateTime());
        $eventRecord->setEventBatch($domainEvent->getEventBatch());
        $eventRecord->setUser($domainEvent->getUser());
        $eventRecord->setResourceKey($domainEvent->getResourceKey());
        $eventRecord->setResourceId($domainEvent->getResourceId());
        $eventRecord->setResourceLocale($domainEvent->getResourceLocale());
        $eventRecord->setResourceTitle($domainEvent->getResourceTitle());
        $eventRecord->setResourceSecurityContext($domainEvent->getResourceSecurityContext());
        $eventRecord->setResourceSecurityType($domainEvent->getResourceSecurityType());

        return $eventRecord;
    }
}
