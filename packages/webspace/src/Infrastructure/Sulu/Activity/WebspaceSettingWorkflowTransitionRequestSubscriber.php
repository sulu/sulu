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

namespace Sulu\Webspace\Infrastructure\Sulu\Activity;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Webspace\Domain\Event\WebspaceSettingWorkflowTransitionRequestEvent;
use Sulu\Webspace\Domain\Model\WebspaceSettingInterface;
use Sulu\Webspace\Domain\Repository\WebspaceSettingRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
final class WebspaceSettingWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WebspaceSettingRepositoryInterface $webspaceSettingRepository,
        private DomainEventCollectorInterface $domainEventCollector,
        private DomainEventDispatcherInterface $domainEventDispatcher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkflowTransitionRequestActionEvent::class => 'onWorkflowTransitionRequestAction',
        ];
    }

    public function onWorkflowTransitionRequestAction(WorkflowTransitionRequestActionEvent $event): void
    {
        if (WebspaceSettingInterface::RESOURCE_KEY !== $event->getResourceKey()) {
            return;
        }

        $webspaceSetting = $this->webspaceSettingRepository->findOneBy(['webspaceKey' => $event->getResourceId()]);

        if (null === $webspaceSetting) {
            return;
        }

        $domainEvent = new WebspaceSettingWorkflowTransitionRequestEvent(
            $webspaceSetting,
            $event->getAction(),
            $event->getLocale(),
            $event->getContext(),
        );

        // The verdicts were written outside the unit of work, so no flush follows that would store a
        // collected activity.
        if ($event->isWrittenWithoutFlush()) {
            $this->domainEventDispatcher->dispatch($domainEvent);

            return;
        }

        $this->domainEventCollector->collect($domainEvent);
    }
}
