<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Functional\Application\ContentWorkflow;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\ActivityBundle\Domain\Model\ActivityInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\Kernel;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

/**
 * The validation run happens on a worker, where nothing flushes after it, so its activity is stored
 * right away, and where nobody is signed in to be named as the actor.
 */
#[CoversNothing]
class WorkflowTransitionRequestActivityTest extends SuluTestCase
{
    use WorkflowTransitionRequestTrait;

    private ContentManagerInterface $contentManager;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->authenticateAsRequestCreator('activity-author');
    }

    public function testWorkerRunStoresAValidatedActivityWithoutAUser(): void
    {
        $this->sendForReview('example-configured-workflow');

        $this->runWorker();

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(Example::RESOURCE_KEY, $activity->getResourceKey());
        $this->assertSame(['approved' => 0, 'rejected' => 1], $activity->getContext());
        $this->assertNull($activity->getUser(), 'Nobody is signed in on a worker.');
    }

    public function testSendingForReviewCommitsNothingBeforeTheCallersFlush(): void
    {
        $example = $this->createExampleAtDraft('example-configured-workflow');

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );

        $tableName = static::getEntityManager()->getClassMetadata(WorkflowTransitionRequest::class)->getTableName();
        $this->assertEquals(
            0,
            static::getEntityManager()->getConnection()->fetchOne(\sprintf('SELECT COUNT(*) FROM %s', $tableName)),
            'The request row has to land with the caller\'s flush, in one transaction with the workflow marking.',
        );
    }

    public function testFinallyFailedRunStoresAValidatedActivityOnce(): void
    {
        $this->sendForReview('example-throwing-workflow');

        $this->runWorker();

        $this->assertCount(1, $this->transport()->getRejected());

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(['approved' => 0, 'rejected' => 1], $activity->getContext());
        $this->assertNull($activity->getUser());
    }

    public function testSeveralValidatorsStoreOneValidatedActivityWithTheFinalCounts(): void
    {
        $this->sendForReview('example-multiple-validators-workflow');

        $this->runWorker();

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(['approved' => 1, 'rejected' => 1], $activity->getContext());
    }

    public function testAReviewersDecisionIsNotCountedAsACheck(): void
    {
        $example = $this->sendForReview('example-multiple-validators-workflow');

        $request = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class)->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
        $request->addApproval($this->createRequestCreator('activity-reviewer'));
        static::getEntityManager()->flush();

        $this->runWorker();

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(['approved' => 1, 'rejected' => 1], $activity->getContext());
    }

    /**
     * The crashing validator keeps its row pending through the retries, so the request is validated only
     * when the failure listener rejects it, with the answer of the healthy validator counted.
     */
    public function testACrashingValidatorAmongSeveralStoresOneValidatedActivityAfterTheLastRetry(): void
    {
        $example = $this->sendForReview('example-multiple-validators-throwing-workflow');

        $this->runWorker();

        $this->assertCount(1, $this->transport()->getRejected());

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(['approved' => 1, 'rejected' => 1], $activity->getContext());

        $request = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class)->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::APPROVED,
            $request->getValidatorDecision('unpublished_example_references')?->getStatus(),
        );
        $this->assertSame(
            'sulu_content.workflow_transition_request.check_errored',
            $request->getValidatorDecision('test_configured_result')?->getMessages()[0]->key,
        );
    }

    private function transport(): InMemoryTransport
    {
        /** @var InMemoryTransport $transport */
        $transport = static::getContainer()->get('messenger.transport.' . Kernel::VALIDATION_TRANSPORT);

        return $transport;
    }

    private function runWorker(): void
    {
        static::getContainer()->get('security.token_storage')->setToken(null);

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $eventDispatcher->addListener(
            WorkerRunningEvent::class,
            static fn (WorkerRunningEvent $event) => $event->isWorkerIdle() ? $event->getWorker()->stop() : null,
        );

        $worker = new Worker(
            [Kernel::VALIDATION_TRANSPORT => $this->transport()],
            static::getContainer()->get(MessageBusInterface::class),
            $eventDispatcher,
        );
        $worker->run(['sleep' => 0]);
    }

    private function sendForReview(string $template): Example
    {
        $example = $this->createExampleAtDraft($template);

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        return $example;
    }

    private function findActivity(string $type): ActivityInterface
    {
        static::getEntityManager()->clear();

        /** @var list<ActivityInterface> $activities */
        $activities = static::getEntityManager()->getRepository(ActivityInterface::class)->findBy(['type' => $type]);
        $this->assertCount(1, $activities, \sprintf('Expected one "%s" activity.', $type));

        return $activities[0];
    }
}
