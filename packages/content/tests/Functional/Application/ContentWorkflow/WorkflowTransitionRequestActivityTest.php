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
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\Kernel;
use Sulu\Content\Tests\Traits\WorkflowTransitionRequestTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Messenger\Worker;

/**
 * The validation run happens on a worker, where nothing but the flush middleware stores what the
 * run collected, and where nobody is signed in to be named as the actor.
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

        $this->assertNotEmpty(
            $this->transport()->getSent()[0]->all(EnableFlushStamp::class),
            'The flush stamp has to travel with the message, or the worker never flushes after the run.',
        );

        $this->runWorker();

        $activity = $this->findActivity('workflow_transition_request.validated');
        $this->assertSame(Example::RESOURCE_KEY, $activity->getResourceKey());
        $this->assertSame(['approved' => 0, 'rejected' => 1], $activity->getContext());
        $this->assertNull($activity->getUser(), 'Nobody is signed in on a worker.');
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

    private function sendForReview(string $template): void
    {
        $example = $this->createExampleAtDraft($template);

        $this->contentManager->applyTransition(
            $example,
            ['stage' => DimensionContentInterface::STAGE_DRAFT, 'locale' => 'en'],
            WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT,
        );
        static::getEntityManager()->flush();
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
