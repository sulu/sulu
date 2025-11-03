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
final class AdminPageIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onPageChanged(PageCreatedEvent|PageModifiedEvent|PageRemovedEvent|PageRestoredEvent|PageTranslationRestoredEvent|PageTranslationAddedEvent|PageTranslationRemovedEvent $event): void
    {
        $resourceId = $event->getResourceId();

        $identifiers = \array_map(
            fn (string $locale) => PageInterface::RESOURCE_KEY . '::' . $resourceId . '::' . $locale,
            $this->getLocales($event),
        );

        if ([] === $identifiers) {
            return;
        }

        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers($identifiers),
        );
    }

    /**
     * @return string[]
     */
    private function getLocales(PageCreatedEvent|PageModifiedEvent|PageRemovedEvent|PageRestoredEvent|PageTranslationRestoredEvent|PageTranslationAddedEvent|PageTranslationRemovedEvent $event): array
    {
        if ($event instanceof PageRemovedEvent) {
            return $event->getAllLocales() ?? [];
        }

        if ($event instanceof PageRestoredEvent) {
            $page = $event->getPage();
            $dimensionContentCollection = new DimensionContentCollection($page->getDimensionContents()->toArray(), [], PageDimensionContent::class);
            $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);

            return $unlocalizedDimensionContent ? ($unlocalizedDimensionContent->getAvailableLocales() ?? []) : [];
        }

        return $event->getResourceLocale() ? [$event->getResourceLocale()] : [];
    }
}
