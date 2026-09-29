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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\ValidateWorkflowTransitionRequestFailureListener;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(ValidateWorkflowTransitionRequestFailureListener::class)]
class ValidateWorkflowTransitionRequestFailureListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testDispatchesValidatedOnceAndFlushesWhenErroredDecisionsAreSettled(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');
        $request->addValidatorDecision('second');
        $request->addValidatorDecision('claimed_elsewhere');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision($request->getValidatorDecision('first'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))->willReturn(true);
        $repository->settleDecision($request->getValidatorDecision('second'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))->willReturn(true);
        $repository->settleDecision($request->getValidatorDecision('claimed_elsewhere'), WorkflowTransitionRequestDecisionStatusEnum::REJECTED, Argument::type('array'))->willReturn(false);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof WorkflowTransitionRequestActionEvent
                && $event->getWorkflowTransitionRequest() === $request
                && WorkflowTransitionRequestActionEvent::VALIDATED === $event->getAction()
                && ['approved' => 0, 'rejected' => 2] === $event->getContext(),
        ))->shouldBeCalledOnce()->willReturnArgument(0);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->isOpen()->willReturn(true);
        $entityManager->flush()->shouldBeCalledOnce();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
            $entityManager->reveal(),
        );

        $listener($this->createFailedEvent($request, false));
    }

    public function testDoesNotFlushAClosedEntityManager(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('first');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->willReturn(true);

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(WorkflowTransitionRequestActionEvent::class))->shouldBeCalledOnce()->willReturnArgument(0);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->isOpen()->willReturn(false);
        $entityManager->flush()->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
            $entityManager->reveal(),
        );

        $listener($this->createFailedEvent($request, false));
    }

    public function testDispatchesNothingWhenNothingIsSettled(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->findOneBy(['id' => $request->getId()])->willReturn($request);
        $repository->settleDecision(Argument::cetera())->shouldNotBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->flush()->shouldNotBeCalled();

        $listener = new ValidateWorkflowTransitionRequestFailureListener(
            $repository->reveal(),
            $eventDispatcher->reveal(),
            $entityManager->reveal(),
        );

        $listener($this->createFailedEvent($request, false));
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
            $this->prophesize(EntityManagerInterface::class)->reveal(),
        );

        $listener($this->createFailedEvent($request, true));
    }

    private function createFailedEvent(WorkflowTransitionRequest $request, bool $willRetry): WorkerMessageFailedEvent
    {
        $event = new WorkerMessageFailedEvent(
            new Envelope(new ValidateWorkflowTransitionRequestMessage($request->getId())),
            'async',
            new \RuntimeException('down'),
        );

        if ($willRetry) {
            $event->setForRetry();
        }

        return $event;
    }
}
