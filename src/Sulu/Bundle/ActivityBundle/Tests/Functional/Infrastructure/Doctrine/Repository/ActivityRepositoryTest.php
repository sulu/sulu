<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Tests\Functional\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Repository\ActivityRepository;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\UserInterface;

class ActivityRepositoryTest extends SuluTestCase
{
    use ProphecyTrait;

    /**
     * @var ActivityRepository
     */
    private $repository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ObjectProphecy<DomainEvent>
     */
    private $domainEvent;

    public function setUp(): void
    {
        static::purgeDatabase();
        $this->entityManager = static::getEntityManager();
        $this->repository = static::getContainer()->get('sulu_activity.activity_repository.doctrine');

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
        $this->domainEvent->getResourceSecurityObjectType()->willReturn('pages');
        $this->domainEvent->getResourceSecurityObjectId()->willReturn('1234-1234-1234-1234');
    }

    public function testCreateForDomainEvent(): void
    {
        $dateTime = new \DateTimeImmutable('2020-01-01');
        $user = $this->prophesize(UserInterface::class);

        $this->domainEvent->getEventDateTime()->willReturn($dateTime);
        $this->domainEvent->getUser()->willReturn($user->reveal());

        $activity = $this->repository->createFromDomainEvent($this->domainEvent->reveal());

        static::assertSame('created', $activity->getType());
        static::assertSame(['relatedPageId' => 'page-123'], $activity->getContext());
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $activity->getPayload());
        static::assertSame($dateTime, $activity->getTimestamp());
        static::assertSame('batch-1234', $activity->getBatch());
        static::assertSame($user->reveal(), $activity->getUser());
        static::assertSame('pages', $activity->getResourceKey());
        static::assertSame('1234-1234-1234-1234', $activity->getResourceId());
        static::assertSame('en', $activity->getResourceLocale());
        static::assertSame('sulu-io', $activity->getResourceWebspaceKey());
        static::assertSame('Test Page 1234', $activity->getResourceTitle());
        static::assertSame('en', $activity->getResourceTitleLocale());
        static::assertSame('sulu.webspaces.sulu-io', $activity->getResourceSecurityContext());
        static::assertSame('pages', $activity->getResourceSecurityObjectType());
        static::assertSame('1234-1234-1234-1234', $activity->getResourceSecurityObjectId());
    }

    public function testAddAndCommit(): void
    {
        $entityRepository = $this->entityManager->getRepository(ActivityInterface::class);

        $activity = $this->repository->createFromDomainEvent($this->domainEvent->reveal());
        $activities = $entityRepository->findAll();
        static::assertCount(0, $activities);

        $this->repository->addAndCommit($activity);
        $activities = $entityRepository->findAll();
        static::assertCount(1, $activities);
        static::assertNull($activities[0]->getPayload());

        $this->repository->addAndCommit($activity);
        static::assertCount(2, $entityRepository->findAll());
    }

    public function testAddAndCommitWithPayload(): void
    {
        // boot kernel with additional configuration and update variables that are set in setUp method
        static::bootKernel(['environment' => 'with_payload']);
        $this->setUp();

        $entityRepository = $this->entityManager->getRepository(ActivityInterface::class);

        $activity = $this->repository->createFromDomainEvent($this->domainEvent->reveal());
        $activities = $entityRepository->findAll();
        static::assertCount(0, $activities);

        $this->repository->addAndCommit($activity);
        $activities = $entityRepository->findAll();
        static::assertCount(1, $activities);
        static::assertSame(['name' => 'name-123', 'description' => 'description-123'], $activities[0]->getPayload());

        $this->repository->addAndCommit($activity);
        $activities = $entityRepository->findAll();
        static::assertCount(2, $activities);
    }
}
