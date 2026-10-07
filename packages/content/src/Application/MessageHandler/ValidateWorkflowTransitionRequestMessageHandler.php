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

namespace Sulu\Content\Application\MessageHandler;

use Psr\Log\LoggerInterface;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowRegistryInterface;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationContext;
use Sulu\Content\Application\RequestWorkflow\Validator\ValidationResult;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Runs the validator its message names, when that check is still pending, and records its verdict. On a
 * worker a crash escapes, so the retry strategy applies to this one validator and no other.
 *
 * @internal
 */
final class ValidateWorkflowTransitionRequestMessageHandler
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly RequestWorkflowRegistryInterface $requestWorkflowRegistry,
        private readonly LoggerInterface $logger,
        private readonly WorkerState $workerState,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ValidateWorkflowTransitionRequestMessage $message): void
    {
        $request = $this->workflowTransitionRequestRepository->findOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        if (null === $request) {
            return;
        }

        // Settled before this pass ran, by a cancel or a publish. Logged because under async
        // processing it is otherwise indistinguishable from a request that is simply gone.
        if (!$request->isOpen()) {
            $this->logger->info('Request "{request}" is no longer open, its pending validator "{validator}" is skipped.', [
                'request' => $request->getId(),
                'validator' => $message->getValidatorKey(),
            ]);

            return;
        }

        $workflowName = $request->getWorkflowName();
        if (!$this->requestWorkflowRegistry->has($workflowName)) {
            $this->logger->warning('Request workflow "{workflow}" is not registered, request "{request}" keeps its pending validator "{validator}".', [
                'workflow' => $workflowName,
                'request' => $request->getId(),
                'validator' => $message->getValidatorKey(),
            ]);

            return;
        }

        $validatorKey = $message->getValidatorKey();
        $decision = $request->getValidatorDecision($validatorKey);
        if (null === $decision || !$decision->isPending()) {
            return;
        }

        $entry = $this->requestWorkflowRegistry->get($workflowName)->validators[$validatorKey] ?? null;

        if (null === $entry) {
            $result = ValidationResult::reject(WorkflowTransitionRequestDecisionMessage::text(\sprintf("Check failed: validator '%s' is not registered", $validatorKey)));
        } else {
            try {
                $result = $entry['validator']->check(new ValidationContext(
                    $request->getResourceKey(),
                    $request->getResourceId(),
                    $request->getLocale(),
                    $workflowName,
                    $entry['config'],
                ));
            } catch (\Throwable $throwable) {
                // The row stays pending, so the retry strategy has this one check left to run.
                if ($this->workerState->isRunning()) {
                    throw $throwable;
                }

                $this->logger->error('Request workflow validator "{validator}" failed on request "{request}".', [
                    'validator' => $validatorKey,
                    'request' => $request->getId(),
                    'exception' => $throwable,
                ]);

                $result = ValidationResult::reject(WorkflowTransitionRequestDecisionMessage::translated('sulu_content.workflow_transition_request.check_errored'));
            }
        }

        $status = $result->approved
            ? WorkflowTransitionRequestDecisionStatusEnum::APPROVED
            : WorkflowTransitionRequestDecisionStatusEnum::REJECTED;

        $counts = $this->workflowTransitionRequestRepository->settleDecision($decision, $status, $result->messages);
        if (null === $counts) {
            return;
        }

        // The counts come from the database, because the other checks of the request settle on other
        // workers. The request counts as validated once none is pending, so the last check to settle
        // reports it.
        if (0 < $counts['pending']) {
            return;
        }

        $this->eventDispatcher->dispatch(new WorkflowTransitionRequestActionEvent(
            $request,
            WorkflowTransitionRequestActionEvent::VALIDATED,
            ['approved' => $counts['approved'], 'rejected' => $counts['rejected']],
        ));
    }
}
