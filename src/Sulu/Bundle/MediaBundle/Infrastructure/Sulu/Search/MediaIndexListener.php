<?php

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
use Sulu\Bundle\MediaBundle\Domain\Event\MediaCreatedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaModifiedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRemovedEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaRestoredEvent;
use Sulu\Bundle\MediaBundle\Domain\Event\MediaVersionAddedEvent;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MediaIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onMediaChanged(MediaCreatedEvent|MediaModifiedEvent|MediaRemovedEvent|MediaRestoredEvent|MediaVersionAddedEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof MediaRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = MediaInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof MediaRestoredEvent || $event instanceof MediaVersionAddedEvent) {
            $media = $event->getMedia();
            $locales = $this->getLocales($media);

            foreach ($locales as $locale) {
                $identifiers[] = MediaInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($locale) {
            $identifiers[] = MediaInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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
    private function getLocales(MediaInterface $media): array
    {
        $locales = [];

        foreach ($media->getFiles() as $file) {
            foreach ($file->getFileVersions() as $fileVersion) {
                foreach ($fileVersion->getMeta() as $meta) {
                    $locales[] = $meta->getLocale();
                }
            }
        }

        return $locales;
    }
}
