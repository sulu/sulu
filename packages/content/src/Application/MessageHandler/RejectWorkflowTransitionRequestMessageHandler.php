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
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
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
        private readonly WorkflowTransitionAdminAuthorizerInterface $workflowTransitionAdminAuthorizer,
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

        // Asked here and not only where a controller dispatches this: the message is on the public
        // bus. The user check above runs first, which keeps the authorizer's system-call bypass out
        // of a verdict that has to be attributable to a person.
        $this->workflowTransitionAdminAuthorizer->assertCanReview(
            $workflowTransitionRequest->getResourceKey(),
            $workflowTransitionRequest->getResourceId(),
            $workflowTransitionRequest->getLocale(),
        );

        $workflowTransitionRequest->addRejection($user, WorkflowTransitionRequestDecisionMessage::text($message->getComment()));

        return $workflowTransitionRequest;
    }
}
