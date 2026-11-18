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

namespace Sulu\Content\Application\ContentWorkflow\Subscriber;

use Sulu\Content\Application\ContentWorkflow\ContentWorkflowInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Refuses a publish by a user while an action of an open request has taken the publish over, for example
 * because the request names a date to publish at.
 *
 * A call without a user passes, because the action itself publishes that way: a scheduled task runs
 * without a token.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestPublishReplacementSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly WorkflowTransitionRequestActionRegistryInterface $actionRegistry,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     */
    public function onPublish(GuardEvent $guardEvent): void
    {
        $dimensionContent = $guardEvent->getSubject();
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        if (null === $this->tokenStorage->getToken()?->getUser()) {
            return;
        }

        $workflowTransitionRequest = $this->activeWorkflowTransitionRequestProvider->findForContent($dimensionContent);
        if (null === $workflowTransitionRequest) {
            return;
        }

        foreach ($this->actionRegistry->all() as $action) {
            if (!$action->replacesPublish($workflowTransitionRequest)) {
                continue;
            }

            $message = \sprintf('The request has taken over the publish. Use its action "%s" to publish.', $action::getKey());

            $guardEvent->addTransitionBlocker(new TransitionBlocker(
                $message,
                ContentWorkflowInterface::BLOCKER_CODE_EXCEPTION,
                [ContentWorkflowInterface::BLOCKER_EXCEPTION_PARAMETER => new AccessDeniedException($message)],
            ));

            return;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.content_workflow.guard.' . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => 'onPublish',
        ];
    }
}
