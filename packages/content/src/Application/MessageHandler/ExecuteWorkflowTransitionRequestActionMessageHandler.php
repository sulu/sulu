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

use Sulu\Content\Application\Message\ExecuteWorkflowTransitionRequestActionMessage;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestActionNotAvailableException;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestClosedException;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;

/**
 * @internal
 */
final class ExecuteWorkflowTransitionRequestActionMessageHandler
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly WorkflowTransitionRequestActionRegistryInterface $actionRegistry,
        private readonly WorkflowTransitionAdminAuthorizerInterface $workflowTransitionAdminAuthorizer,
    ) {
    }

    public function __invoke(ExecuteWorkflowTransitionRequestActionMessage $message): WorkflowTransitionRequest
    {
        $workflowTransitionRequest = $this->workflowTransitionRequestRepository->getOneBy([
            'id' => $message->getWorkflowTransitionRequestId(),
        ]);

        if (!$workflowTransitionRequest->isOpen()) {
            throw new WorkflowTransitionRequestClosedException($workflowTransitionRequest);
        }

        $action = $this->actionRegistry->get($message->getActionKey());

        // The controller has resolved the key already, this guards a message sent by other code.
        if (null === $action || !$action->isAvailable($workflowTransitionRequest)) {
            throw new WorkflowTransitionRequestActionNotAvailableException($workflowTransitionRequest, $message->getActionKey());
        }

        // Asked here and not only where the controller dispatches this: the message is on the public bus.
        $this->workflowTransitionAdminAuthorizer->assertHasPermission(
            $workflowTransitionRequest->getResourceKey(),
            $workflowTransitionRequest->getResourceId(),
            $workflowTransitionRequest->getLocale(),
            $action->getPermission(),
        );

        $action->execute($workflowTransitionRequest);

        return $workflowTransitionRequest;
    }
}
