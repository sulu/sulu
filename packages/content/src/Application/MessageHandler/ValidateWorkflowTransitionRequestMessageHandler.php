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
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;

/**
 * Runs every validator with a pending row and records its verdict. On a worker a crash escapes so
 * the retry strategy applies.
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
            $this->logger->info('Request "{request}" is no longer open, its pending validators are skipped.', [
                'request' => $request->getId(),
            ]);

            return;
        }

        $workflowName = $request->getWorkflowName();
        if (!$this->requestWorkflowRegistry->has($workflowName)) {
            $this->logger->warning('Request workflow "{workflow}" is not registered, request "{request}" keeps its pending validators.', [
                'workflow' => $workflowName,
                'request' => $request->getId(),
            ]);

            return;
        }

        $entriesByKey = $this->requestWorkflowRegistry->get($workflowName)->validators;

        foreach ($request->getDecisions() as $decision) {
            $validatorKey = $decision->getValidatorKey();
            if (null === $validatorKey || !$decision->isPending()) {
                continue;
            }

            $entry = $entriesByKey[$validatorKey] ?? null;

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

            $this->workflowTransitionRequestRepository->settleDecision(
                $decision,
                $result->approved
                    ? WorkflowTransitionRequestDecisionStatusEnum::APPROVED
                    : WorkflowTransitionRequestDecisionStatusEnum::REJECTED,
                $result->messages,
            );
        }
    }
}
