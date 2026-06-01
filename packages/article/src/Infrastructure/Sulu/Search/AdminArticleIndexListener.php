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
use Sulu\Article\Domain\Event\ArticleCopiedEvent;
use Sulu\Article\Domain\Event\ArticleCreatedEvent;
use Sulu\Article\Domain\Event\ArticleModifiedEvent;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleRestoredEvent;
use Sulu\Article\Domain\Event\ArticleTranslationAddedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationCopiedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRestoredEvent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class AdminArticleIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onArticleChanged(ArticleCreatedEvent|ArticleCopiedEvent|ArticleModifiedEvent|ArticleRemovedEvent|ArticleRestoredEvent|ArticleTranslationRestoredEvent|ArticleTranslationAddedEvent|ArticleTranslationRemovedEvent|ArticleTranslationCopiedEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof ArticleRemovedEvent || $event instanceof ArticleRestoredEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = ArticleInterface::RESOURCE_KEY . '__' . $event->getResourceId() . '__' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = ArticleInterface::RESOURCE_KEY . '__' . $event->getResourceId() . '__' . $locale;
        }

        if (!$identifiers) {
            return;
        }

        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers($identifiers),
        );
    }
}
