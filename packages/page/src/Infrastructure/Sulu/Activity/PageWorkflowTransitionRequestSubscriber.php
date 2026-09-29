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

namespace Sulu\Page\Infrastructure\Sulu\Activity;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Event\PageWorkflowTransitionRequestEvent;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class PageWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PageRepositoryInterface $pageRepository,
        private DomainEventCollectorInterface $domainEventCollector,
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
        $request = $event->getWorkflowTransitionRequest();

        if (PageInterface::RESOURCE_KEY !== $request->getResourceKey()) {
            return;
        }

        $locale = $request->getLocale();

        // The content of the request's locale is loaded, so the event can resolve the title.
        $page = $this->pageRepository->findOneBy(
            ['uuid' => $request->getResourceId()],
            [
                PageRepositoryInterface::SELECT_PAGE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => [$locale],
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ],
        );

        if (null === $page) {
            return;
        }

        $this->domainEventCollector->collect(
            new PageWorkflowTransitionRequestEvent($page, $event->getAction(), $locale, $event->getContext()),
        );
    }
}
