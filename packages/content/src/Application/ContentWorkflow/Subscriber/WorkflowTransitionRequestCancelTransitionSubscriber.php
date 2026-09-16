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

use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Closes the active request on the content transitions that take content out of a review place
 * without publishing it: `cancel_review`, `cancel_review_draft`, `reject` and `reject_draft`.
 *
 * A reviewer's rejection is a vote recorded on the request and leaves it open, because the request
 * can still reach its approval threshold. These are the content-level transitions, which move the
 * content itself out of review, so the request ends with it.
 *
 * What each of them takes as a permission is decided by the guards in
 * `WorkflowTransitionAuthorizationSubscriber`, so a transition that reaches this class is allowed.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestCancelTransitionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
    ) {
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    public function onLeaveReview(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $this->activeWorkflowTransitionRequestProvider->findForContent($dimensionContent)?->cancel();
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.transition.';

        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onLeaveReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onLeaveReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onLeaveReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onLeaveReview',
        ];
    }
}
