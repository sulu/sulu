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
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class WorkflowTransitionRequestPublishTransitionSubscriber implements EventSubscriberInterface
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
    public function onPublish(TransitionEvent $transitionEvent): void
    {
        $dimensionContent = $transitionEvent->getSubject();

        if (!$dimensionContent instanceof DimensionContentInterface) {
            return;
        }

        $workflowTransitionRequest = $this->activeWorkflowTransitionRequestProvider->findForContent($dimensionContent);

        $workflowTransitionRequest?->publish();
    }

    public static function getSubscribedEvents(): array
    {
        $prefix = 'workflow.content_workflow.transition.';

        return [
            $prefix . WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH => ['onPublish', -100],
        ];
    }
}
