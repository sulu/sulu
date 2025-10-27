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
use Sulu\Article\Domain\Event\ArticleCreatedEvent;
use Sulu\Article\Domain\Event\ArticleModifiedEvent;
use Sulu\Article\Domain\Event\ArticleRemovedEvent;
use Sulu\Article\Domain\Event\ArticleRestoredEvent;
use Sulu\Article\Domain\Event\ArticleTranslationAddedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationCopiedEvent;
use Sulu\Article\Domain\Event\ArticleTranslationRemovedEvent;
use Sulu\Article\Domain\Model\ArticleDimensionContent;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class ArticleIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onArticleChanged(ArticleCreatedEvent|ArticleModifiedEvent|ArticleRemovedEvent|ArticleRestoredEvent|ArticleTranslationAddedEvent|ArticleTranslationRemovedEvent|ArticleTranslationCopiedEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof ArticleRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = ArticleInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof ArticleRestoredEvent) {
            $article = $event->getArticle();
            $dimensionContentCollection = new DimensionContentCollection($article->getDimensionContents()->toArray(), [], ArticleDimensionContent::class);
            $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);

            if (!$unlocalizedDimensionContent?->getAvailableLocales()) {
                return;
            }

            foreach ($unlocalizedDimensionContent->getAvailableLocales() as $locale) {
                $identifiers[] = ArticleInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = ArticleInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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
