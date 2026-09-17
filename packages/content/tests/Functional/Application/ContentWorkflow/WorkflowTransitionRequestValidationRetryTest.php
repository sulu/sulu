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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\Kernel;
use Sulu\Content\Tests\Traits\ConsumeTransportTrait;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

/**
 * A validator that crashes on a worker must not be swallowed: the exception escapes the handler so
 * the transport's retry strategy applies, and only the final failure writes the rejection. The test
 * transport retries zero times, so one worker run is that final failure.
 */
#[CoversNothing]
class WorkflowTransitionRequestValidationRetryTest extends SuluTestCase
{
    use ConsumeTransportTrait;
    use WorkflowTransitionRequestTrait;

    private const THROWING_TEMPLATE = 'example-throwing-workflow';

    private ContentManagerInterface $contentManager;

    private WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->contentManager = static::getContainer()->get(ContentManagerInterface::class);
        $this->workflowTransitionRequestRepository = static::getContainer()->get(WorkflowTransitionRequestRepositoryInterface::class);
        $this->authenticateAsRequestCreator('retry-author');
    }

    public function testCrashingValidatorIsRejectedAfterTheWorkerRanOutOfRetries(): void
    {
        $example = $this->sendForReview();

        $this->runWorker();

        $this->assertCount(
            1,
            $this->transport()->getRejected(),
            'The handler must let the exception escape on a worker, so the transport sees a failed message.',
        );

        static::getEntityManager()->clear();
        $reviewer = $this->findRequest($example)->getValidatorDecision('test_configured_result');

        $this->assertNotNull($reviewer);
        $this->assertSame(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, $reviewer->getStatus());
        $this->assertSame(
            'sulu_content.workflow_transition_request.check_errored',
            $reviewer->getMessages()[0]->key,
        );
        $this->assertNull(
            $reviewer->getMessages()[0]->text,
            'The exception text belongs in the log, never in what a reviewer reads.',
        );
    }

    public function testCrashingValidatorIsRejectedImmediatelyOutsideAWorker(): void
    {
        $example = $this->sendForReview();

        $this->consumeTransport(Kernel::VALIDATION_TRANSPORT);

        $this->assertSame([], $this->transport()->getRejected());

        static::getEntityManager()->clear();
        $reviewer = $this->findRequest($example)->getValidatorDecision('test_configured_result');

        $this->assertNotNull($reviewer);
        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::REJECTED,
            $reviewer->getStatus(),
            'Inside the author\'s request there is nothing to retry, so the rejection is stored right away.',
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
        /** @var EventDispatcherInterface&\Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher */
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

    private function sendForReview(): Example
    {
        $example = $this->createExampleAtDraft(self::THROWING_TEMPLATE);

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();

        return $example;
    }

    private function findRequest(Example $example): WorkflowTransitionRequest
    {
        return $this->workflowTransitionRequestRepository->getOneBy([
            'resourceKey' => Example::RESOURCE_KEY,
            'resourceId' => (string) $example->getId(),
            'locale' => 'en',
            'active' => true,
        ]);
    }
}
