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
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\ValidateWorkflowTransitionRequestFailureListener;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecision;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(ValidateWorkflowTransitionRequestFailureListener::class)]
class ValidateWorkflowTransitionRequestFailureListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testRejectsOnlyTheNamedValidatorAndDispatchesValidatedWithTheCountsFromTheDatabase(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('answered');
        $request->addValidatorDecision('first');
        $request->addValidatorDecision('second');
        $request->getValidatorDecision('answered')?->settle(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [], new \DateTimeImmutable());

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('first'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))
            ->shouldBeCalledOnce()
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, ['approved' => 4, 'rejected' => 2, 'pending' => 0]));
        $repository->settleDecision($request->getValidatorDecision('second'), Argument::cetera())->shouldNotBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof WorkflowTransitionRequestActionEvent
                && $event->getWorkflowTransitionRequest() === $request
                && WorkflowTransitionRequestActionEvent::VALIDATED === $event->getAction()
                && ['approved' => 4, 'rejected' => 2] === $event->getContext(),
        ))->shouldBeCalledOnce()->willReturnArgument(0);

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
        );

        $listener($this->createFailedEvent($request, 'first', false));

        $this->assertTrue($request->getValidatorDecision('second')?->isPending());
    }

    public function testDispatchesNothingWhenTheValidatorAnsweredInTheMeantime(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');
        $request->getValidatorDecision('first')?->settle(WorkflowTransitionRequestDecisionStatusEnum::APPROVED, [], new \DateTimeImmutable());

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
        );

        $listener($this->createFailedEvent($request, 'first', false));
    }

    public function testDispatchesNothingWhenAnotherWriterClaimedTheRow(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('first'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))
            ->willReturn(null);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
        );

        $listener($this->createFailedEvent($request, 'first', false));
    }

    public function testDispatchesNothingWhileAnotherCheckIsStillPending(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('first'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))
            ->will($this->settleAs(WorkflowTransitionRequestDecisionStatusEnum::REJECTED, ['approved' => 0, 'rejected' => 1, 'pending' => 1]));

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
        );

        $listener($this->createFailedEvent($request, 'first', false));
    }

    public function testDoesNothingWhenTheMessageWillBeRetried(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(Argument::any())->shouldNotBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
        );

        $listener($this->createFailedEvent($request, 'first', true));
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

    private function createFailedEvent(WorkflowTransitionRequest $request, string $validatorKey, bool $willRetry): WorkerMessageFailedEvent
    {
        $event = new WorkerMessageFailedEvent(
            new Envelope(new ValidateWorkflowTransitionRequestMessage($request->getId(), $validatorKey)),
            'async',
            new \RuntimeException('down'),
        );

        if ($willRetry) {
            $event->setForRetry();
        }

        return $event;
    }
}
