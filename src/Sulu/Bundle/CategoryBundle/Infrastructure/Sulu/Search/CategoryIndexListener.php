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

namespace Sulu\Bundle\CategoryBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryCreatedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryModifiedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRemovedEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryRestoredEvent;
use Sulu\Bundle\CategoryBundle\Domain\Event\CategoryTranslationAddedEvent;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class CategoryIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onCategoryChanged(CategoryCreatedEvent|CategoryModifiedEvent|CategoryRemovedEvent|CategoryTranslationAddedEvent|CategoryRestoredEvent $event): void
    {
        $locale = $event->getResourceLocale();
        $identifiers = [];

        if ($event instanceof CategoryRemovedEvent) {
            $locales = $event->getAllLocales();

            if (!$locales) {
                return;
            }

            foreach ($locales as $locale) {
                $identifiers[] = CategoryInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
            }
        } elseif ($event instanceof CategoryRestoredEvent) {
            $category = $event->getCategory();

            foreach ($category->getTranslations() as $translation) {
                if (!$translation->getLocale()) {
                    continue;
                }

                $identifiers[] = CategoryInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $translation->getLocale();
            }
        } elseif ($locale) {
            $identifiers[] = CategoryInterface::RESOURCE_KEY . '::' . $event->getResourceId() . '::' . $locale;
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
