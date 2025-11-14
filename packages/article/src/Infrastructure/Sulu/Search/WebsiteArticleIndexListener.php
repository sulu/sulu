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

namespace Sulu\Article\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Event\ArticleWorkflowTransitionAppliedEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class WebsiteArticleIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onArticleChanged(ArticleWorkflowTransitionAppliedEvent|ArticleRemovedEvent|ArticleTranslationRemovedEvent $event): void
    {
        $resourceId = $event->getResourceId();

        $identifiers = \array_map(
            fn (string $locale) => ArticleInterface::RESOURCE_KEY . '::' . $resourceId . '::' . $locale,
            $this->getLocales($event),
        );

        if ([] === $identifiers) {
            return;
        }

        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('website')
                ->withIdentifiers($identifiers),
        );
    }

    /**
     * @return string[]
     */
    private function getLocales(ArticleWorkflowTransitionAppliedEvent|ArticleRemovedEvent|ArticleTranslationRemovedEvent $event): array
    {
        if ($event instanceof ArticleRemovedEvent) {
            return $event->getAllLocales() ?? [];
        }

        return $event->getResourceLocale() ? [$event->getResourceLocale()] : [];
    }
}
