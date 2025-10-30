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

namespace Sulu\Page\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Page\Domain\Event\PageCreatedEvent;
use Sulu\Page\Domain\Event\PageModifiedEvent;
use Sulu\Page\Domain\Event\PageRemovedEvent;
use Sulu\Page\Domain\Event\PageRestoredEvent;
use Sulu\Page\Domain\Event\PageTranslationAddedEvent;
use Sulu\Page\Domain\Event\PageTranslationRemovedEvent;
use Sulu\Page\Domain\Event\PageTranslationRestoredEvent;
use Sulu\Page\Domain\Model\PageDimensionContent;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class PageIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    // Todo: Check if translation removed and translation restored event is needed.
    public function onPageChanged(PageCreatedEvent|PageModifiedEvent|PageRemovedEvent|PageRestoredEvent|PageTranslationRestoredEvent|PageTranslationAddedEvent|PageTranslationRemovedEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof PageRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = PageInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof PageRestoredEvent) {
            $page = $event->getPage();
            $dimensionContentCollection = new DimensionContentCollection($page->getDimensionContents()->toArray(), [], PageDimensionContent::class);
            $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);

            if (!$unlocalizedDimensionContent?->getAvailableLocales()) {
                return;
            }

            foreach ($unlocalizedDimensionContent->getAvailableLocales() as $locale) {
                $identifiers[] = PageInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = PageInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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
