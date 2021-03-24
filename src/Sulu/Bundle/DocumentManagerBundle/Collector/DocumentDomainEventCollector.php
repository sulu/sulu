<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Collector;

use Sulu\Bundle\EventLogBundle\Application\Dispatcher\DomainEventDispatcherInterface;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Component\DocumentManager\Event\ClearEvent;
use Sulu\Component\DocumentManager\Event\FlushEvent;
use Sulu\Component\DocumentManager\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentDomainEventCollector implements DocumentDomainEventCollectorInterface, EventSubscriberInterface
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

    public static function getSubscribedEvents()
    {
        return [
            Events::CLEAR => ['clearCollectedEvents', -256],
            Events::FLUSH => ['dispatchCollectedEvents', -256],
        ];
    }

    public function collect(DomainEvent $domainEvent): void
    {
        $this->eventsToBeDispatched[] = $domainEvent;
    }

    public function clearCollectedEvents(ClearEvent $event): void
    {
        $this->eventsToBeDispatched = [];
    }

    public function dispatchCollectedEvents(FlushEvent $event): void
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
