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

namespace Sulu\Bundle\MediaBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\CollectionRestoredEvent;
use Sulu\Bundle\MediaBundle\Entity\CollectionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class AdminCollectionIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onCollectionChanged(CollectionCreatedEvent|CollectionModifiedEvent|CollectionRemovedEvent|CollectionRestoredEvent $event): void
    {
        $resourceId = $event->getResourceId();

        $identifiers = \array_map(
            fn (string $locale) => CollectionInterface::RESOURCE_KEY . '::' . $resourceId . '::' . $locale,
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
    private function getLocales(CollectionCreatedEvent|CollectionModifiedEvent|CollectionRemovedEvent|CollectionRestoredEvent $event): array
    {
        if ($event instanceof CollectionRemovedEvent) {
            return $event->getAllLocales() ?? [];
        }

        if ($event instanceof CollectionRestoredEvent) {
            $locales = [];
            $collection = $event->getCollection();
            foreach ($collection->getMeta() as $meta) {
                $locales[] = $meta->getLocale();
            }

            return $locales;
        }

        return $event->getResourceLocale() ? [$event->getResourceLocale()] : [];
    }
}
