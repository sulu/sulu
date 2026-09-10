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

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\Message\RejectWorkflowTransitionRequestMessage;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal
 */
final class RejectWorkflowTransitionRequestMessageHandler
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function __invoke(RejectWorkflowTransitionRequestMessage $message): WorkflowTransitionRequest
    {
        $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        $user = $this->tokenStorage?->getToken()?->getUser();
        if (!$user instanceof UserInterface) {
            throw new MissingAuthenticatedUserException('reject a workflow transition request');
        }

        $workflowTransitionRequest->addRejection($user, WorkflowTransitionRequestDecisionMessage::text($message->getComment()));

        return $workflowTransitionRequest;
    }
}
