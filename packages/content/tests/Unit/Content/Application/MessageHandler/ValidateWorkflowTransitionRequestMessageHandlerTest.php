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
        )->shouldBeCalledOnce()->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED));

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

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));
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

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));

        $this->assertSame(
            WorkflowTransitionRequestDecisionStatusEnum::PENDING,
            $request->getDecisions()[0]->getStatus(),
            'A workflow dropped from the configuration cannot answer for its validators.',
        );
    }

    /**
     * On a worker the failure is handed back to the retry strategy, but only after the validators
     * behind it have run: stopping at the first crash left them pending across every retry, and the
     * failure listener then rejected them as errored without ever having checked them.
     */
    public function testOnAWorkerAFailingValidatorDoesNotHoldUpTheOthers(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('exploding');
        $request->addValidatorDecision('healthy');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(
            $request->getValidatorDecision('exploding'),
            Argument::cetera(),
        )->shouldNotBeCalled();
        $repository->settleDecision(
            $request->getValidatorDecision('healthy'),
            WorkflowTransitionRequestDecisionStatusEnum::APPROVED,
            [],
        )->shouldBeCalledOnce()->willReturn(true);

        $exploding = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $exploding->check(Argument::any())->willThrow(new \RuntimeException('remote service down'));

        $healthy = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $healthy->check(Argument::any())->willReturn(ValidationResult::approve());

        $registry = $this->prophesize(RequestWorkflowRegistryInterface::class);
        $registry->has('default')->willReturn(true);
        $registry->get('default')->willReturn(new RequestWorkflow('default', [
            'exploding' => ['validator' => $exploding->reveal(), 'config' => [], 'required' => false],
            'healthy' => ['validator' => $healthy->reveal(), 'config' => [], 'required' => false],
        ], 1));

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::cetera())->shouldNotBeCalled();

        $workerState = new WorkerState();
        $workerState->onWorkerStarted();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $registry->reveal(),
            $logger->reveal(),
            $workerState,
            $this->expectNoEvent()->reveal(),
        );

        try {
            $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));
            $this->fail('Expected the validator failure to reach the retry strategy.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('remote service down', $exception->getMessage());
        }
    }

    public function testDispatchesOnceWithTheCountsOverAllChecks(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('answered_before');
        $request->addValidatorDecision('passing');
        $request->addValidatorDecision('failing');
        $request->addValidatorDecision('claimed_elsewhere');
        $request->getValidatorDecision('answered_before')?->settle(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, [], new \DateTimeImmutable());
        $message = WorkflowTransitionRequestDecisionMessage::text('Not good enough');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('passing'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [])
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::APPROVED));
        $repository->settleDecision($request->getValidatorDecision('failing'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, [$message])
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED));
        $repository->settleDecision($request->getValidatorDecision('claimed_elsewhere'), WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [])
            ->willReturn(false);

        $approving = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $approving->check(Argument::any())->willReturn(ValidationResult::approve());
        $rejecting = $this->prophesize(RequestWorkflowValidatorInterface::class);
        $rejecting->check(Argument::any())->willReturn(ValidationResult::reject($message));

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry([
                'passing' => $approving->reveal(),
                'failing' => $rejecting->reveal(),
                'claimed_elsewhere' => $approving->reveal(),
            ]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectValidatedEvent($request, 1, 2)->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));
    }

    public function testDoesNotDispatchWhenNoDecisionIsPending(): void
    {
        $request = $this->createRequest();
        $request->addValidatorDecision('passing');
        $request->getValidatorDecision('passing')?->settle(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [], new \DateTimeImmutable());

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => 'request-1'])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $handler = new ValidateWorkflowTransitionRequestMessageHandler(
            $repository->reveal(),
            $this->createRegistry(['passing' => $this->prophesize(RequestWorkflowValidatorInterface::class)->reveal()]),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new WorkerState(),
            $this->expectNoEvent()->reveal(),
        );

        $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));
    }

    private function settleAs(WorkflowTransitionRequestDecisionStatusEnum $status): callable
    {
        return static function(array $arguments) use ($status): bool {
            /** @var WorkflowTransitionRequestDecision $decision */
            $decision = $arguments[0];
            /** @var list<WorkflowTransitionRequestDecisionMessage> $messages */
            $messages = $arguments[2];
            $decision->settle($status, $messages, new \DateTimeImmutable());

            return true;
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
