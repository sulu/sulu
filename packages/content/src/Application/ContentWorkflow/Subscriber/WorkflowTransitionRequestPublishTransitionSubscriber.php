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

use Sulu\Content\Application\RequestWorkflow\WorkflowTransitionRequestStatusResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Action\WorkflowTransitionRequestActionRegistryInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestSettledEvent;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Value\WorkflowTransitionRequest\WorkflowTransitionRequestStatusEnum;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestPublishTransitionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly WorkflowTransitionRequestStatusResolverInterface $statusResolver,
        private readonly WorkflowTransitionRequestActionRegistryInterface $actionRegistry,
    ) {
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    public function onPublish(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $workflowTransitionRequest = $this->activeWorkflowTransitionRequestProvider->findForContent($dimensionContent);

        if (null === $workflowTransitionRequest) {
            return;
        }

        if ($this->isBypassingReview($workflowTransitionRequest)) {
            $workflowTransitionRequest->bypass();
        } else {
            $workflowTransitionRequest->publish();
        }

        $this->eventDispatcher->dispatch(new WorkflowTransitionRequestSettledEvent($workflowTransitionRequest));
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.transition.';

        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => ['onPublish', -100],
        ];
    }

    /**
     * A publish that an action of a bundle carries out, such as a scheduled one, is what the request
     * asked for, even while it waits for approvals.
     */
    private function isBypassingReview(WorkflowTransitionRequest $workflowTransitionRequest): bool
    {
        if (WorkflowTransitionRequestStatusEnum::APPROVED === $this->statusResolver->resolve($workflowTransitionRequest)) {
            return false;
        }

        foreach ($this->actionRegistry->all() as $action) {
            if ($action->replacesPublish($workflowTransitionRequest)) {
                return false;
            }
        }

        return true;
    }
}
