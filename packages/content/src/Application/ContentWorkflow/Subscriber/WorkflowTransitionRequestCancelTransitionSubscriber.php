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

use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestCancelNotAllowedException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
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
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestCancelTransitionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly SecurityCheckerInterface $securityChecker,
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
    ) {
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    public function onCancelReview(TransitionEvent $transitionEvent): void
    {
        $activeRequest = $this->findActiveRequest($transitionEvent);
        if (null === $activeRequest) {
            return;
        }

        if (!$this->hasEditPermission($activeRequest)) {
            throw new WorkflowTransitionRequestCancelNotAllowedException($activeRequest);
        }

        $activeRequest->cancel();
    }

    /**
     * Rejecting already takes the `review` permission through the guard on this transition, so there
     * is nothing further to ask here.
     *
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    public function onReject(TransitionEvent $transitionEvent): void
    {
        $this->findActiveRequest($transitionEvent)?->cancel();
    }

    /**
     * Withdrawing a request frees the content for editing again, so it takes the same permission as
     * editing it: the author withdraws their own, a colleague unblocks content left behind.
     */
    private function hasEditPermission(WorkflowTransitionRequest $request): bool
    {
        return $this->securityChecker->hasPermission(
            $this->securityContextResolver->resolve(
                $request->getResourceKey(),
                $request->getResourceId(),
                $request->getLocale(),
            ),
            PermissionTypes::EDIT,
        );
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    private function findActiveRequest(TransitionEvent $transitionEvent): ?WorkflowTransitionRequest
    {
        $dimensionContent = $transitionEvent->getSubject();
        if (!$dimensionContent instanceof DimensionContentInterface) {
            return null;
        }

        return $this->activeWorkflowTransitionRequestProvider->findForContent($dimensionContent);
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.transition.';

        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onCancelReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onCancelReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onReject',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onReject',
        ];
    }
}
