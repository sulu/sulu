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
use Sulu\Content\Application\Message\ValidateWorkflowTransitionRequestMessage;
use Sulu\Content\Application\RequestWorkflow\RequestWorkflowResolverInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\DuplicateActiveWorkflowTransitionRequestException;
use Sulu\Content\Domain\Exception\MissingAuthenticatedUserException;
use Sulu\Content\Domain\Exception\NoRequestWorkflowException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Domain\Model\WorkflowTransitionRequest\WorkflowTransitionRequest;
use Sulu\Content\Domain\Repository\WorkflowTransitionRequestRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Workflow\Event\TransitionEvent;

/**
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestTransitionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly WorkflowTransitionRequestRepositoryInterface $workflowTransitionRequestRepository,
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestWorkflowResolverInterface $requestWorkflowResolver,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @template T of object
     *
     * @param TransitionEvent<T> $transitionEvent
     */
    public function onRequestForReview(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $resourceKey = $dimensionContent::getResourceKey();
        $resourceId = (string) $dimensionContent->getResource()->getId();
        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        $workflow = $this->requestWorkflowResolver->resolveForContent($dimensionContent);
        if (null === $workflow) {
            throw new NoRequestWorkflowException($resourceKey, $resourceId, $locale);
        }

        if (null !== $this->activeWorkflowTransitionRequestProvider->find($resourceKey, $resourceId, $locale)) {
            throw new DuplicateActiveWorkflowTransitionRequestException($resourceKey, $resourceId, $locale);
        }

        $workflowTransitionRequest = new WorkflowTransitionRequest(
            $resourceKey,
            $resourceId,
            $locale,
            $workflow->name,
        );
        $workflowTransitionRequest->setCreator($this->resolveUser());

        // One pending row per validator. Whether it blocks is read from the config on every status
        // check, so it is not copied here.
        foreach (\array_keys($workflow->validators) as $validatorKey) {
            $workflowTransitionRequest->addValidatorDecision($validatorKey);
        }

        $this->workflowTransitionRequestRepository->add($workflowTransitionRequest);

        // No flush: the row and the workflow marking must land in one transaction, and Symfony
        // writes the marking only after these events return.
        $this->messageBus->dispatch(new Envelope(
            new ValidateWorkflowTransitionRequestMessage($workflowTransitionRequest->getId()),
            [new DispatchAfterCurrentBusStamp()],
        ));
    }

    private function resolveUser(): UserInterface
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof UserInterface) {
            throw new MissingAuthenticatedUserException('create a workflow transition request');
        }

        return $user;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW => 'onRequestForReview',
            'workflow.content_workflow.transition.' . WorkflowInterface::WORKFLOW_TRANSITION_REQUEST_FOR_REVIEW_DRAFT => 'onRequestForReview',
        ];
    }
}
