<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Application\Collector;

use Sulu\Bundle\ActivityBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class DomainEventCollector implements DomainEventCollectorInterface
{
    /**
     * @var DomainEvent[]
     */
    private $eventsToBeDispatched = [];

    public function __construct(
        private DomainEventDispatcherInterface $domainEventDispatcher
    ) {
    }

    public function collect(DomainEvent $domainEvent): void
    {
        $this->eventsToBeDispatched[] = $domainEvent;
    }

    public function clear(): void
    {
        $this->eventsToBeDispatched = [];
    }

    public function dispatch(): void
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
