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
use Sulu\Content\Application\Message\RetryWorkflowTransitionRequestValidationMessage;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\RetryWorkflowTransitionRequestValidationMessageHandler;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[CoversClass(RetryWorkflowTransitionRequestValidationMessageHandler::class)]
class RetryWorkflowTransitionRequestValidationMessageHandlerTest extends TestCase
{
    use ProphecyTrait;

    public function testInvokeDispatchesTheRetriedEventAndAFlushedValidation(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->addValidatorDecision('seo_required');

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $request->getId()])->willReturn($request);

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::that(
            static fn (Envelope $envelope) => $envelope->getMessage() instanceof ValidateWorkflowTransitionRequestMessage
                && [] !== $envelope->all(DispatchAfterCurrentBusStamp::class)
                && [] !== $envelope->all(EnableFlushStamp::class),
        ))->shouldBeCalledOnce()->willReturn(new Envelope(new \stdClass()));

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::that(
            static fn (object $event) => $event instanceof WorkflowTransitionRequestActionEvent
                && $event->getWorkflowTransitionRequest() === $request
                && WorkflowTransitionRequestActionEvent::VALIDATION_RETRIED === $event->getAction()
                && ['validatorKey' => 'seo_required'] === $event->getContext(),
        ))->shouldBeCalledOnce()->willReturnArgument(0);

        $handler = new RetryWorkflowTransitionRequestValidationMessageHandler(
            $repository->reveal(),
            $messageBus->reveal(),
            $eventDispatcher->reveal(),
        );

        $this->assertSame($request, $handler(new RetryWorkflowTransitionRequestValidationMessage($request->getId(), 'seo_required')));
    }

    public function testInvokeDispatchesNothingForAClosedRequest(): void
    {
        $request = new WorkflowTransitionRequest('pages', 'res-1', 'en', 'default');
        $request->cancel();

        $repository = $this->prophesize(WorkflowTransitionRequestRepositoryInterface::class);
        $repository->getOneBy(['id' => $request->getId()])->willReturn($request);

        $messageBus = $this->prophesize(MessageBusInterface::class);
        $messageBus->dispatch(Argument::any())->shouldNotBeCalled();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $handler = new RetryWorkflowTransitionRequestValidationMessageHandler(
            $repository->reveal(),
            $messageBus->reveal(),
            $eventDispatcher->reveal(),
        );

        $this->expectException(WorkflowTransitionRequestClosedException::class);
        $handler(new RetryWorkflowTransitionRequestValidationMessage($request->getId(), 'seo_required'));
    }
}
