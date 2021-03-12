<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Collector;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\EventLogBundle\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\EventLogBundle\Event\DomainEvent;

class DoctrineDomainEventCollector implements DoctrineDomainEventCollectorInterface, EventSubscriber
{
    /**
     * @var DomainEventDispatcherInterface
     */
    private $domainEventDispatcher;

    /**
     * @var DomainEvent[]
     */
    private $eventsToBeDispatched = [];

    public function __construct(
        DomainEventDispatcherInterface $domainEventDispatcher
    ) {
        $this->domainEventDispatcher = $domainEventDispatcher;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onClear,
            Events::postFlush,
        ];
    }

    public function collect(DomainEvent $domainEvent): void
    {
        $this->eventsToBeDispatched[] = $domainEvent;
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->eventsToBeDispatched = [];
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $batchIdentifier = \uniqid('', true);
        $batchEvents = $this->eventsToBeDispatched;

        $this->eventsToBeDispatched = [];

        foreach ($batchEvents as $domainEvent) {
            if (!$domainEvent->getEventBatch()) {
                $domainEvent->setEventBatch($batchIdentifier);
            }

            $this->domainEventDispatcher->dispatch($domainEvent);
        }
    }
}
