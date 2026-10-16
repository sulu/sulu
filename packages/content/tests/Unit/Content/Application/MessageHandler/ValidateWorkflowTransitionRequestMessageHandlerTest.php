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

namespace Sulu\Content\Tests\Unit\Content\Application\MessageHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\ValidateWorkflowTransitionRequestMessageHandler;
use Sulu\Content\Application\MessageHandler\WorkerState;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(ValidateWorkflowTransitionRequestMessageHandler::class)]
final class ValidateWorkflowTransitionRequestMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testThrowingValidatorRejectsItsOwnRowWithTheExceptionMessageInsideTheRequest(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('exploding');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(
            $request->getValidatorDecision('exploding'),
            WorkflowTransitionRequestDecisionStatusEnum::REJECTED,
            [WorkflowTransitionRequestDecisionMessage::translated('sulu_content.workflow_transition_request.check_errored')],
        )->shouldBeCalledOnce()->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, ['approved' => 0, 'rejected' => 1, 'pending' => 0]));

        $exploding = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $exploding->check(Argument::any())->willThrow(new \RuntimeException('remote service down'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::cetera())->shouldBeCalledOnce();

        $registry = $this->prophesize(RequestWorkflowRegistryInterface::class);
        $registry->has('default')->willReturn(true);
        $registry->get('default')->willReturn(new RequestWorkflow('default', [
            'exploding' => ['validator' => $exploding->reveal(), 'config' => [], 'required' => false],
        ], 1));

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $registry->reveal(),
            $logger->reveal(),
            new WorkerState(),
            $this->expectValidatedEvent($request, 0, 1)->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'exploding'));
    }

    public function testUnregisteredWorkflowIsLoggedAndLeavesTheRowsPending(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('unpublished_references');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $registry = $this->prophesize(RequestWorkflowRegistryInterface::class);
        $registry->has('default')->willReturn(false);
        $registry->get(Argument::any())->shouldNotBeCalled();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::cetera())->shouldBeCalledOnce();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $registry->reveal(),
            $logger->reveal(),
            new WorkerState(),
            $this->expectNoEvent()->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'unpublished_references'));

        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::PENDING,
            $request->getDecisions()[0]->getStatus(),
            'A workflow dropped from the configuration cannot answer for its validators.',
        );
    }

    /**
     * On a worker the failure is handed back to the retry strategy, which re-runs this one message.
     * The row stays pending, so the failure listener can still reject it once the retries are used up.
     */
    public function testOnAWorkerAFailingValidatorKeepsItsRowPendingAndRethrows(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('exploding');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $exploding = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $exploding->check(Argument::any())->willThrow(new \RuntimeException('remote service down'));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::cetera())->shouldNotBeCalled();

        $workerState = new WorkerState();
        $workerState->onWorkerStarted();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['exploding' => $exploding->reveal()]),
            $logger->reveal(),
            $workerState,
            $this->expectNoEvent()->reveal(),
        );

        try {
            $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'exploding'));
            $this->fail('Expected the validator failure to reach the retry strategy.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('remote service down', $exception->getMessage());
        }

        $this->assertTrue($request->getValidatorDecision('exploding')?->isPending());
    }

    public function testRunsOnlyTheValidatorTheMessageNamesAndStaysQuietWhileAnotherIsPending(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('named');
        $request->addValidatorDecision('other');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('named'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [])
            ->shouldBeCalledOnce()
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, ['approved' => 1, 'rejected' => 0, 'pending' => 1]));
        $repository->settleDecision($request->getValidatorDecision('other'), Argument::cetera())->shouldNotBeCalled();

        $named = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $named->check(Argument::any())->willReturn(ValidationResult::approve())->shouldBeCalledOnce();
        $other = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $other->check(Argument::any())->shouldNotBeCalled();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['named' => $named->reveal(), 'other' => $other->reveal()]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectNoEvent()->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'named'));

        $this->assertTrue($request->getValidatorDecision('other')?->isPending());
    }

    public function testRejectsAValidatorThatIsNoLongerRegistered(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('removed');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(
            $request->getValidatorDecision('removed'),
            WorkflowTransitionRequestDecisionStatusEnum::REJECTED,
            [WorkflowTransitionRequestDecisionMessage::text("Check failed: validator 'removed' is not registered")],
        )->shouldBeCalledOnce()->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, ['approved' => 0, 'rejected' => 1, 'pending' => 0]));

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry([]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectValidatedEvent($request, 0, 1)->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'removed'));
    }

    public function testDispatchesValidatedWithTheCountsFromTheDatabaseOnceNoCheckIsPending(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('failing');
        $message = WorkflowTransitionRequestDecisionMessage::text('Not good enough');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('failing'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, [$message])
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, ['approved' => 3, 'rejected' => 1, 'pending' => 0]));

        $rejecting = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $rejecting->check(Argument::any())->willReturn(ValidationResult::reject($message));

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['failing' => $rejecting->reveal()]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectValidatedEvent($request, 3, 1)->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'failing'));
    }

    public function testDoesNotDispatchWhenAnotherWriterClaimedTheRow(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('claimed_elsewhere');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('claimed_elsewhere'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [])
            ->willReturn(null);

        $approving = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $approving->check(Argument::any())->willReturn(ValidationResult::approve());

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['claimed_elsewhere' => $approving->reveal()]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectNoEvent()->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'claimed_elsewhere'));
    }

    public function testDoesNotDispatchWhenTheDecisionIsNotPending(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('passing');
        $request->getValidatorDecision('passing')?->settle(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [], new \DateTimeImmutable());

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $validator = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $validator->check(Argument::any())->shouldNotBeCalled();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['passing' => $validator->reveal()]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectNoEvent()->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1', 'passing'));
    }

    /**
     * @param array{approved: int, rejected: int, pending: int} $counts
     */
    private function settleAs(WorkflowTransitionRequestDecisionStatusEnum $status, array $counts): callable
    {
        return static function(array $arguments) use ($status, $counts): array {
            /** @var WorkflowTransitionRequestDecision $decision */
            $decision = $arguments[0];
            /** @var list<WorkflowTransitionRequestDecisionMessage> $messages */
            $messages = $arguments[2];
            $decision->settle($status, $messages, new \DateTimeImmutable());

            return $counts;
        };
    }

    /**
     * @param array<string, RequestWorkflowValidatorInterface> $validators
     */
    private function createRegistry(array $validators): RequestWorkflowRegistryInterface
    {
        $entries = [];
        foreach ($validators as $key => $validator) {
            $entries[$key] = ['validator' => $validator, 'config' => [], 'required' => false];
        }

        $registry = $this->prophesize(RequestWorkflowRegistryInterface::class);
        $registry->has('default')->willReturn(true);
        $registry->get('default')->willReturn(new RequestWorkflow('default', $entries, 1));

        return $registry->reveal();
    }

    /**
     * @return ObjectProphecy<EventDispatcherInterface>
     */
    private function expectValidatedEvent(WorkflowTransitionRequest $request, int $approved, int $rejected): ObjectProphecy
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof WorkflowTransitionRequestActionEvent
                && $event->getWorkflowTransitionRequest() === $request
                && WorkflowTransitionRequestActionEvent::VALIDATED === $event->getAction()
                && ['approved' => $approved, 'rejected' => $rejected] === $event->getContext(),
        ))->shouldBeCalledOnce()->willReturnArgument(0);

        return $eventDispatcher;
    }

    /**
     * @return ObjectProphecy<EventDispatcherInterface>
     */
    private function expectNoEvent(): ObjectProphecy
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        return $eventDispatcher;
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest('pages', 'resource-1', 'en', 'default');
    }
}
