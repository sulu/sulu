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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Every write applies `edit`, so this guard refuses them while an open request covers the content.
 * The preview persists nothing, so its routes pass.
 *
 * @final
 *
 * @internal this class is internal and should not be extended from or used in another context
 */
class ContentReviewLockSubscriber implements EventSubscriberInterface
{
    /**
     * Preview routes that map form data.
     */
    public const PREVIEW_ROUTES = [
        'sulu_preview.render',
        'sulu_preview.update',
        'sulu_preview.update-context',
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
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

        if (!$dimensionContent instanceof DimensionContentInterface || $this->isPreview()) {
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

    private function isPreview(): bool
    {
        $route = $this->requestStack->getMainRequest()?->attributes->get('_route');

        return \in_array($route, self::PREVIEW_ROUTES, true);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.content_workflow.guard.' . WorkflowInterface::WORKFLOW_TRANSITION_EDIT => 'onEdit',
        ];
    }
}
