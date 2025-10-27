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

final class CollectionIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onCollectionChanged(CollectionCreatedEvent|CollectionModifiedEvent|CollectionRemovedEvent|CollectionRestoredEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof CollectionRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = CollectionInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof CollectionRestoredEvent) {
            $collection = $event->getCollection();
            $locales = $this->getLocales($collection);

            foreach ($locales as $locale) {
                $identifiers[] = CollectionInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = CollectionInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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

    /**
     * @return string[]
     */
    private function getLocales(CollectionInterface $collection): array
    {
        $locales = [];

        foreach ($collection->getMeta() as $meta) {
            $locales[] = $meta->getLocale();
        }

        return $locales;
    }
}
