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
use Sulu\Content\Application\WorkflowTransitionRequest\ActiveWorkflowTransitionRequestProviderInterface;
use Sulu\Content\Domain\Exception\ContentInReviewException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Holds content that an open request covers: every write goes through the `edit` transition, so this
 * is the one place that decides it, whatever content type or controller asked.
 *
 * Publishing, rejecting and cancelling leave the review and write only the live stage, so they never
 * reach this guard.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class ContentReviewLockSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ActiveWorkflowTransitionRequestProviderInterface $activeWorkflowTransitionRequestProvider,
    ) {
    }

    /**
     * @template T of object
     *
     * @param GuardEvent<T> $guardEvent
     */
    public function onEdit(GuardEvent $guardEvent): void
    {
        $dimensionContent = $guardEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $resourceKey = $dimensionContent::getResourceKey();
        $resourceId = (string) $dimensionContent->getResource()->getId();
        /** @var string $locale */
        $locale = $dimensionContent->getLocale();

        if (null === $this->activeWorkflowTransitionRequestProvider->find($resourceKey, $resourceId, $locale)) {
            return;
        }

        $exception = new ContentInReviewException($resourceKey, $resourceId, $locale);

        $guardEvent->addTransitionBlocker(new TransitionBlocker(
            $exception->getMessage(),
            ContentWorkflowInterface::BLOCKER_CODE_EXCEPTION,
            [ContentWorkflowInterface::BLOCKER_EXCEPTION_PARAMETER => $exception],
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.content_workflow.guard.' . WorkflowInterface::WORKFLOW_TRANSITION_EDIT => 'onEdit',
        ];
    }
}
