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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\AccountRestoredEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactCreatedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactModifiedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactRemovedEvent;
use Sulu\Bundle\ContactBundle\Domain\Event\ContactRestoredEvent;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal this class is internal no backwards compatibility promise is given for this class
 *           use Symfony Dependency Injection to override or create your own Listener instead
 */
final class ContactIndexListener
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function onAccountChanged(AccountCreatedEvent|AccountModifiedEvent|AccountRemovedEvent|AccountRestoredEvent $event): void
    {
        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers([
                    AccountInterface::RESOURCE_KEY . '::' . $event->getResourceId(),
                ]),
        );
    }

    public function onContactChanged(ContactCreatedEvent|ContactModifiedEvent|ContactRemovedEvent|ContactRestoredEvent $event): void
    {
        $this->messageBus->dispatch(
            ReindexConfig::create()
                ->withIndex('admin')
                ->withIdentifiers([
                    ContactInterface::RESOURCE_KEY . '::' . $event->getResourceId(),
                ]),
        );
    }
}
