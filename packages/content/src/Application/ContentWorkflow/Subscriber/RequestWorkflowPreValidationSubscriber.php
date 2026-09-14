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

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\RequestWorkflow\PreValidator\PreValidationContext;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Domain\Exception\WorkflowTransitionRequestPreValidationFailedException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * Runs the workflow's pre-validators on every transition that would take content live. A failure
 * aborts the transition before anything is written.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class RequestWorkflowPreValidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestWorkflowResolverInterface $requestWorkflowResolver,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     *
     * @throws WorkflowTransitionRequestPreValidationFailedException
     */
    public function onTransition(TransitionEvent $transitionEvent): void
    {
        // A command, a fixture or a consumer publishes on the system's behalf, with no editor to tell
        // what is missing. `sulu:page:initialize` publishes an empty homepage this way.
        if (!$this->tokenStorage->getToken()?->getUser() instanceof UserInterface) {
            return;
        }

        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $workflow = $this->requestWorkflowResolver->resolveForContent($dimensionContent);
        if (null === $workflow) {
            return;
        }

        // Passed checks travel too, so the author sees what is done as well as what is left.
        $results = [];
        $passed = true;
        foreach ($workflow->preValidators as $key => $entry) {
            $context = new PreValidationContext($dimensionContent, $entry['config'], $workflow->name);
            $result = $entry['pre_validator']->check($context);

            $results[] = [
                'key' => $key,
                'passed' => $result->approved,
                'messages' => $result->messagesToArray(),
            ];
            $passed = $passed && $result->approved;
        }

        if ($passed) {
            return;
        }

        throw new WorkflowTransitionRequestPreValidationFailedException($results);
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.transition.';

        // Ahead of every other subscriber: nothing may be written before the content passes.
        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW => ['onTransition', 200],
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT => ['onTransition', 200],
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => ['onTransition', 200],
        ];
    }
}
