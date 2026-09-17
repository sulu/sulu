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
use Psr\Log\LoggerInterface;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\MessageHandler\ValidateWorkflowTransitionRequestMessageHandler;
use Sulu\Content\Application\MessageHandler\WorkerState;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflow;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\RequestWorkflowValidatorInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;

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
        )->shouldBeCalledOnce();

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
        )->shouldBeCalledOnce();

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
        );

        try {
            $handler(new ValidateWorkflowTransitionRequestMessage('request-1'));
            $this->fail('Expected the validator failure to reach the retry strategy.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('remote service down', $exception->getMessage());
        }
    }

    private function createRequest(): WorkflowTransitionRequest
    {
        return new WorkflowTransitionRequest('pages', 'resource-1', 'en', 'default');
    }
}
