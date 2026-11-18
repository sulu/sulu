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

namespace Sulu\Webspace\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Webspace\Domain\Event\WebspaceSettingCreatedEvent;
use Sulu\Webspace\Domain\Event\WebspaceSettingModifiedEvent;
use Sulu\Webspace\Domain\Event\WebspaceSettingTranslationCopiedEvent;
use Sulu\Webspace\Domain\Model\WebspaceSettingInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class AdminWebspaceSettingIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onWebspaceSettingChanged(
        WebspaceSettingCreatedEvent|WebspaceSettingModifiedEvent|WebspaceSettingTranslationCopiedEvent $event,
    ): void {
        $locale = $event->getResourceLocale();

        if (null === $locale) {
            return;
        }

        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers([WebspaceSettingInterface::RESOURCE_KEY . '__' . $event->getResourceId() . '__' . $locale]),
        );
    }
}
