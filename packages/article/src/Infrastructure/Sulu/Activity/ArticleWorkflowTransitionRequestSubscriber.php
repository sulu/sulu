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

namespace Sulu\Article\Infrastructure\Sulu\Activity;

use Sulu\Article\Domain\Event\ArticleWorkflowTransitionRequestEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Content\Application\WorkflowTransitionRequest\Event\WorkflowTransitionRequestActionEvent;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal No BC promise is given for this class. Create your own event subscriber or use the
 * Symfony DependencyInjection container to override this service.
 */
class ArticleWorkflowTransitionRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
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
        if (ArticleInterface::RESOURCE_KEY !== $event->getResourceKey()) {
            return;
        }

        $locale = $event->getLocale();

        // The content of the request's locale is loaded, so the event can resolve the title.
        $article = $this->articleRepository->findOneBy(
            ['uuid' => $event->getResourceId()],
            [
                ArticleRepositoryInterface::SELECT_ARTICLE_CONTENT => [
                    'selects' => [DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_ADMIN => true],
                    'dimensionAttributes' => [
                        'locale' => [$locale],
                        'stage' => [DimensionContentInterface::STAGE_DRAFT, DimensionContentInterface::STAGE_LIVE],
                    ],
                ],
            ],
        );

        if (null === $article) {
            return;
        }

        $domainEvent = new ArticleWorkflowTransitionRequestEvent($article, $event->getAction(), $locale, $event->getContext());

        // The verdicts were written outside the unit of work, so no flush follows that would store a
        // collected activity.
        if ($event->isWrittenWithoutFlush()) {
            $this->domainEventDispatcher->dispatch($domainEvent);

            return;
        }

        $this->domainEventCollector->collect($domainEvent);
    }
}
