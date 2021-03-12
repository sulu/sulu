<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Dispatcher;

use Sulu\Bundle\EventLogBundle\Event\DomainEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DomainEventDispatcher implements DomainEventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function dispatch(DomainEvent $event): DomainEvent
    {
        /** @var DomainEvent $dispatchedEvent */
        $dispatchedEvent = $this->eventDispatcher->dispatch($event, DomainEvent::class);

        return $dispatchedEvent;
    }
}
