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

namespace Sulu\Snippet\Infrastructure\Sulu\Activity;

use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Domain\Event\SnippetWorkflowTransitionRequestEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Domain\Repository\SnippetRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class SnippetWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private SnippetRepositoryInterface $snippetRepository,
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

        if (SnippetInterface::RESOURCE_KEY !== $request->getResourceKey()) {
            return;
        }

        $locale = $request->getLocale();

        // The content of the request's locale is loaded, so the event can resolve the title.
        $snippet = $this->snippetRepository->findOneBy(
            ['uuid' => $request->getResourceId()],
            [
                SnippetRepositoryInterface::SELECT_SNIPPET_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => $locale,
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ],
        );

        if (null === $snippet) {
            return;
        }

        $this->domainEventCollector->collect(
            new SnippetWorkflowTransitionRequestEvent($snippet, $event->getAction(), $locale, $event->getContext()),
        );
    }
}
