<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;
use Sulu\Bundle\EventLogBundle\Infrastructure\Doctrine\Repository\EventRecordRepository;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\Behavior\SecurityBehavior;
use Sulu\Component\Security\Authentication\UserInterface;

class EventRecordRepositoryTest extends SuluTestCase
{
    /**
     * @var EventRecordRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DomainEvent|ObjectProphecy
     */
    private $domainEvent;

    public function setUp(): void
    {
        static::purgeDatabase();
        $this->entityManager = static::getEntityManager();
        $this->repository = static::getContainer()->get('sulu_event_log.event_record_repository.doctrine');

        $this->domainEvent = $this->prophesize(DomainEvent::class);
        $this->domainEvent->getEventType()->willReturn('created');
        $this->domainEvent->getEventContext()->willReturn(['relatedPageId' => 'page-123']);
        $this->domainEvent->getEventPayload()->willReturn(['name' => 'name-123', 'description' => 'description-123']);
        $this->domainEvent->getEventDateTime()->willReturn(new \DateTimeImmutable());
        $this->domainEvent->getEventBatch()->willReturn('batch-1234');
        $this->domainEvent->getUser()->willReturn(null);
        $this->domainEvent->getResourceKey()->willReturn('pages');
        $this->domainEvent->getResourceId()->willReturn('1234-1234-1234-1234');
        $this->domainEvent->getResourceLocale()->willReturn('en');
        $this->domainEvent->getResourceWebspaceKey()->willReturn('sulu-io');
        $this->domainEvent->getResourceTitle()->willReturn('Test Page 1234');
        $this->domainEvent->getResourceTitleLocale()->willReturn('en');
        $this->domainEvent->getResourceSecurityContext()->willReturn('sulu.webspaces.sulu-io');
        $this->domainEvent->getResourceSecurityObjectType()->willReturn(SecurityBehavior::class);
        $this->domainEvent->getResourceSecurityObjectId()->willReturn('1234-1234-1234-1234');
    }

    public function testCreateForDomainEvent(): void
    {
        $dateTime = new \DateTimeImmutable('2020-01-01');
        $user = $this->prophesize(UserInterface::class);

        $this->domainEvent->getEventDateTime()->willReturn($dateTime);
        $this->domainEvent->getUser()->willReturn($user->reveal());

        $eventRecord = $this->repository->createForDomainEvent($this->domainEvent->reveal());

        static::assertSame('created', $eventRecord->getEventType());
        static::assertSame(['relatedPageId' => 'page-123'], $eventRecord->getEventContext());
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $eventRecord->getEventPayload());
        static::assertSame($dateTime, $eventRecord->getEventDateTime());
        static::assertSame('batch-1234', $eventRecord->getEventBatch());
        static::assertSame($user->reveal(), $eventRecord->getUser());
        static::assertSame('pages', $eventRecord->getResourceKey());
        static::assertSame('1234-1234-1234-1234', $eventRecord->getResourceId());
        static::assertSame('en', $eventRecord->getResourceLocale());
        static::assertSame('sulu-io', $eventRecord->getResourceWebspaceKey());
        static::assertSame('Test Page 1234', $eventRecord->getResourceTitle());
        static::assertSame('en', $eventRecord->getResourceTitleLocale());
        static::assertSame('sulu.webspaces.sulu-io', $eventRecord->getResourceSecurityContext());
        static::assertSame(SecurityBehavior::class, $eventRecord->getResourceSecurityObjectType());
        static::assertSame('1234-1234-1234-1234', $eventRecord->getResourceSecurityObjectId());
    }

    public function testAddAndCommit(): void
    {
        $entityRepository = $this->entityManager->getRepository(EventRecordInterface::class);

        $eventRecord = $this->repository->createForDomainEvent($this->domainEvent->reveal());
        static::assertCount(0, $entityRepository->findAll());

        $this->repository->add($eventRecord);
        static::assertCount(0, $entityRepository->findAll());

        $this->repository->commit();
        static::assertCount(1, $entityRepository->findAll());
    }
}
