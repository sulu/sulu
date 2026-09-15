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
use Sulu\Content\Application\Message\ApproveWorkflowTransitionRequestMessage;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequestDecisionMessage;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @internal
 */
final class ApproveWorkflowTransitionRequestMessageHandler
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly ?TokenStorageInterface $tokenStorage = null,
    ) {
    }

    public function __invoke(ApproveWorkflowTransitionRequestMessage $message): WorkflowTransitionRequest
    {
        $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        $user = $this->tokenStorage?->getToken()?->getUser();
        if (!$user instanceof UserInterface) {
            throw new MissingAuthenticatedUserException('approve a workflow transition request');
        }

        $comment = $message->getComment();
        $workflowTransitionRequest->addApproval(
            $user,
            ...(null === $comment || '' === \trim($comment) ? [] : [WorkflowTransitionRequestDecisionMessage::text($comment)]),
        );

        return $workflowTransitionRequest;
    }
}
