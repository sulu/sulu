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

namespace Sulu\Notifier\Application\Subscriber;

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;
use Sulu\Notifier\Application\Notifier\EventNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal no backwards compatibility promise is given for this class, create your own service
 *           implementing EventSubscriberInterface if you want to overwrite it
 */
final class EventNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EventNotifier $notifier)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [DomainEvent::class => ['onEvent', -512]];
    }

    public function onEvent(object $event): void
    {
        $this->notifier->notify($event);
    }
}
