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

use Sulu\Content\Application\Message\RetryWorkflowTransitionRequestValidationMessage;
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * @internal
 */
final class RetryWorkflowTransitionRequestValidationMessageHandler
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RetryWorkflowTransitionRequestValidationMessage $message): WorkflowTransitionRequest
    {
        $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        if (!$workflowTransitionRequest->isOpen()) {
            // Re-running a check on a closed request would wipe the verdict it answered with, and the
            // validate handler refuses to run on it anyway, so the row would stay pending forever.
            throw new WorkflowTransitionRequestClosedException($workflowTransitionRequest);
        }

        $workflowTransitionRequest->getValidatorDecision($message->getValidatorKey())?->resetToPending();

        // Held back until the reset row is flushed, so the validation run reads a pending row and
        // can claim it. A double dispatch is harmless, the first answer wins.
        $this->messageBus->dispatch(new Envelope(
            new ValidateWorkflowTransitionRequestMessage($workflowTransitionRequest->getId()),
            [new DispatchAfterCurrentBusStamp()],
        ));

        return $workflowTransitionRequest;
    }
}
