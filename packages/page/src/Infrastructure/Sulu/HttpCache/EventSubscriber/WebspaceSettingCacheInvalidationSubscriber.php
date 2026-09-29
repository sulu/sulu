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

namespace Sulu\Page\Infrastructure\Sulu\HttpCache\EventSubscriber;

use Sulu\Bundle\HttpCacheBundle\Cache\CacheManagerInterface;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Page\Domain\Event\WebspaceSettingWorkflowTransitionAppliedEvent;
use Sulu\Page\Domain\Model\WebspaceSettingInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Invalidates by the reference tag of the settings, so it has an effect only with a cache which supports tags.
 * There is no fallback like the one of the pages, because the URLs of the pages using the settings are not known.
 *
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
final class WebspaceSettingCacheInvalidationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ?CacheManagerInterface $cacheManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WebspaceSettingWorkflowTransitionAppliedEvent::class => 'onWorkflowTransition',
        ];
    }

    public function onWorkflowTransition(WebspaceSettingWorkflowTransitionAppliedEvent $event): void
    {
        if (!\in_array($event->getWorkflowTransitionName(), [
            WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
            WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH,
        ], true)) {
            return;
        }

        $this->cacheManager?->invalidateReference(WebspaceSettingInterface::RESOURCE_KEY, $event->getResourceWebspaceKey());
    }
}
