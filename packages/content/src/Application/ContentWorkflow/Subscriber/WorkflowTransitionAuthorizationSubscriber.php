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
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Application\Security\WorkflowTransitionAdminAuthorizerInterface;
use Sulu\Content\Application\Security\WorkflowTransitionRequestSecurityContextResolverInterface;
use Sulu\Content\Domain\Exception\UnresolvableSecurityContextException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Guards the transitions that take a permission of their own, so every admin caller of the workflow
 * is covered and no controller or message handler there has to ask.
 *
 * Registered in the admin context only, because that is where Sulu's permissions live. Code running
 * in the website kernel reaches the workflow with no guard in front of it and has to bring its own
 * check.
 *
 * It blocks rather than throws: the workflow also asks its guards to list the transitions a subject
 * could take, and a throw there would escape a question nobody asked.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionAuthorizationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowTransitionAdminAuthorizerInterface $workflowTransitionAdminAuthorizer,
        private readonly RequestWorkflowResolverInterface $requestWorkflowResolver,
        private readonly WorkflowTransitionRequestSecurityContextResolverInterface $securityContextResolver,
    ) {
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     */
    public function onPublish(GuardEvent $guardEvent): void
    {
        $this->guard($guardEvent, function(string $resourceKey, string $resourceId, string $locale): void {
            $this->workflowTransitionAdminAuthorizer->assertCanPublish($resourceKey, $resourceId, $locale);
        });
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     */
    public function onReject(GuardEvent $guardEvent): void
    {
        $this->guard($guardEvent, function(string $resourceKey, string $resourceId, string $locale): void {
            $this->workflowTransitionAdminAuthorizer->assertCanReview($resourceKey, $resourceId, $locale);
        });
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     */
    public function onCancelReview(GuardEvent $guardEvent): void
    {
        $this->guard($guardEvent, function(string $resourceKey, string $resourceId, string $locale): void {
            $this->workflowTransitionAdminAuthorizer->assertCanCancelReview($resourceKey, $resourceId, $locale);
        });
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     * @param callable(string, string, string): void $assert
     */
    private function guard(GuardEvent $guardEvent, callable $assert): void
    {
        $dimensionContent = $guardEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        // A resource key without a security context has nothing to be checked against, which is only
        // an error once a request workflow covers the content. Until then it publishes as before.
        if (!$this->securityContextResolver->has($dimensionContent::getResourceKey())
            && null === $this->requestWorkflowResolver->resolveForContent($dimensionContent)
        ) {
            return;
        }

        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        try {
            $assert(
                $dimensionContent::getResourceKey(),
                (string) $dimensionContent->getResource()->getId(),
                $locale,
            );
        } catch (AccessDeniedException|UnresolvableSecurityContextException $exception) {
            $guardEvent->addTransitionBlocker(new TransitionBlocker(
                $exception->getMessage(),
                ContentWorkflowInterface::BLOCKER_CODE_EXCEPTION,
                [ContentWorkflowInterface::BLOCKER_EXCEPTION_PARAMETER => $exception],
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.guard.';

        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => 'onPublish',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT => 'onReject',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REJECT_DRAFT => 'onReject',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW => 'onCancelReview',
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_CANCEL_REVIEW_DRAFT => 'onCancelReview',
        ];
    }
}
