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

namespace Sulu\Snippet\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Content\Domain\Model\DimensionContentCollection;
use Sulu\Snippet\Domain\Event\SnippetCreatedEvent;
use Sulu\Snippet\Domain\Event\SnippetModifiedEvent;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetRestoredEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationAddedEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Model\SnippetDimensionContent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class SnippetIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    // Todo: Check if translation removed and translation restored event is needed.
    public function onSnippetChanged(SnippetCreatedEvent|SnippetModifiedEvent|SnippetRemovedEvent|SnippetRestoredEvent|SnippetTranslationAddedEvent|SnippetTranslationRemovedEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof SnippetRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = SnippetInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof SnippetRestoredEvent) {
            $snippet = $event->getSnippet();
            $dimensionContentCollection = new DimensionContentCollection($snippet->getDimensionContents()->toArray(), [], SnippetDimensionContent::class);
            $unlocalizedDimensionContent = $dimensionContentCollection->getDimensionContent(['locale' => null, 'stage' => 'draft']);

            if (!$unlocalizedDimensionContent?->getAvailableLocales()) {
                return;
            }

            foreach ($unlocalizedDimensionContent->getAvailableLocales() as $locale) {
                $identifiers[] = SnippetInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = SnippetInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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
