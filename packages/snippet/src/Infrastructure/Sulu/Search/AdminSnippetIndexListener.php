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
use Sulu\Snippet\Domain\Event\SnippetCreatedEvent;
use Sulu\Snippet\Domain\Event\SnippetModifiedEvent;
use Sulu\Snippet\Domain\Event\SnippetRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetRestoredEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationAddedEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationRemovedEvent;
use Sulu\Snippet\Domain\Event\SnippetTranslationRestoredEvent;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class AdminSnippetIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onSnippetChanged(SnippetCreatedEvent|SnippetModifiedEvent|SnippetRemovedEvent|SnippetRestoredEvent|SnippetTranslationRestoredEvent|SnippetTranslationAddedEvent|SnippetTranslationRemovedEvent $event): void
    {
        $resourceId = $event->getResourceId();

        $identifiers = \array_map(
            fn (string $locale) => SnippetInterface::RESOURCE_KEY . '__' . $resourceId . '__' . $locale,
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
    private function getLocales(SnippetCreatedEvent|SnippetModifiedEvent|SnippetRemovedEvent|SnippetRestoredEvent|SnippetTranslationRestoredEvent|SnippetTranslationAddedEvent|SnippetTranslationRemovedEvent $event): array
    {
        if ($event instanceof SnippetRemovedEvent || $event instanceof SnippetRestoredEvent) {
            return $event->getAllLocales() ?? [];
        }

        return $event->getResourceLocale() ? [$event->getResourceLocale()] : [];
    }
}
