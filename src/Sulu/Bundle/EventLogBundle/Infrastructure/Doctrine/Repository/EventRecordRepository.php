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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Domain\Repository\EventRecordRepositoryInterface;

class EventRecordRepository implements EventRecordRepositoryInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EntityRepository<EventRecordInterface>
     */
    protected $entityRepository;

    /**
     * @var bool
     */
    protected $shouldPersistPayload;

    public function __construct(EntityManager $entityManager, bool $shouldPersistPayload)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(EventRecordInterface::class);
        $this->shouldPersistPayload = $shouldPersistPayload;
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
        $eventRecord->setResourceSecurityObjectType($domainEvent->getResourceSecurityObjectType());
        $eventRecord->setResourceSecurityObjectId($domainEvent->getResourceSecurityObjectId());

        if ($this->shouldPersistPayload) {
            $eventRecord->setEventPayload($domainEvent->getEventPayload());
        }

        return $eventRecord;
    }

    protected function getInsertQueryBuilder(EventRecordInterface $eventRecord): QueryBuilder
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->entityRepository->getClassName());

        $queryBuilder = $this->entityManager->getConnection()->createQueryBuilder();
        $queryBuilder
            ->insert($classMetadata->getTableName())
            ->setValue($classMetadata->getColumnName('eventType'), $queryBuilder->createNamedParameter($eventRecord->getEventType()))
            ->setValue($classMetadata->getColumnName('eventContext'), $queryBuilder->createNamedParameter(\json_encode($eventRecord->getEventContext())))
            ->setValue($classMetadata->getColumnName('eventDateTime'), $queryBuilder->createNamedParameter($eventRecord->getEventDateTime()->format('Y-m-d H:i:s')))
            ->setValue($classMetadata->getColumnName('eventBatch'), $queryBuilder->createNamedParameter($eventRecord->getEventBatch()))
            ->setValue($classMetadata->getColumnName('resourceKey'), $queryBuilder->createNamedParameter($eventRecord->getResourceKey()))
            ->setValue($classMetadata->getColumnName('resourceId'), $queryBuilder->createNamedParameter($eventRecord->getResourceId()))
            ->setValue($classMetadata->getColumnName('resourceLocale'), $queryBuilder->createNamedParameter($eventRecord->getResourceLocale()))
            ->setValue($classMetadata->getColumnName('resourceWebspaceKey'), $queryBuilder->createNamedParameter($eventRecord->getResourceWebspaceKey()))
            ->setValue($classMetadata->getColumnName('resourceTitle'), $queryBuilder->createNamedParameter($eventRecord->getResourceTitle()))
            ->setValue($classMetadata->getColumnName('resourceTitleLocale'), $queryBuilder->createNamedParameter($eventRecord->getResourceTitleLocale()))
            ->setValue($classMetadata->getColumnName('resourceSecurityContext'), $queryBuilder->createNamedParameter($eventRecord->getResourceSecurityContext()))
            ->setValue($classMetadata->getColumnName('resourceSecurityObjectType'), $queryBuilder->createNamedParameter($eventRecord->getResourceSecurityObjectType()))
            ->setValue($classMetadata->getColumnName('resourceSecurityObjectId'), $queryBuilder->createNamedParameter($eventRecord->getResourceSecurityObjectId()));

        if (null !== $eventRecord->getUser()) {
            $queryBuilder->setValue($classMetadata->getColumnName('userId'), $queryBuilder->createNamedParameter($eventRecord->getUser()->getId()));
        }

        if ($this->shouldPersistPayload) {
            $queryBuilder->setValue($classMetadata->getColumnName('eventPayload'), $queryBuilder->createNamedParameter(\json_encode($eventRecord->getEventPayload())));
        }

        // set value of id explicitly if class has a pre-insert identity-generator to be compatible with postgresql
        if (!$classMetadata->idGenerator->isPostInsertGenerator()) {
            $queryBuilder->setValue($classMetadata->getColumnName('id'), $classMetadata->idGenerator->generate($this->entityManager, $eventRecord));
        }

        return $queryBuilder;
    }

    public function addAndCommit(EventRecordInterface $eventRecord): void
    {
        // use query-builder to insert only given entity instead of flushing all managed entities via the entity-manager
        // this prevents flushing unrelated changes and allows to call this method in a postFlush event-listener
        $queryBuilder = $this->getInsertQueryBuilder($eventRecord);
        $queryBuilder->execute();
    }
}
