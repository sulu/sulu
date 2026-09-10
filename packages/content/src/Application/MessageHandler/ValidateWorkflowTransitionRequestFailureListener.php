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

use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionStatusEnum;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Once a validation message has used up its retries, validators that never answered are rejected
 * with the exception message.
 *
 * @internal
 */
final class ValidateWorkflowTransitionRequestFailureListener
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
    ) {
    }

    public function __invoke(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($event->willRetry() || !$message instanceof ValidateWorkflowTransitionRequestMessage) {
            return;
        }

        $request = $this->workflowTransitionRequestRepository->findOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        if (null === $request) {
            return;
        }

        foreach ($request->getDecisions() as $decision) {
            if (null === $decision->getValidatorKey() || !$decision->isPending()) {
                continue;
            }

            $this->workflowTransitionRequestRepository->settleDecision(
                $decision,
                WorkflowTransitionRequestDecisionStatusEnum::REJECTED,
                [WorkflowTransitionRequestDecisionMessage::translated('sulu_content.workflow_transition_request.check_errored')],
            );
        }
    }
}
