<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;

class DomainEventCollectorSubscriber implements EventSubscriber
{
    /**
     * @var DomainEventCollectorInterface
     */
    private $domainEventCollector;

    public function __construct(
        DomainEventCollectorInterface $domainEventDispatcher
    ) {
        $this->domainEventCollector = $domainEventDispatcher;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onClear,
            Events::postFlush,
        ];
    }

    public function onClear(OnClearEventArgs $args): void
    {
        $this->domainEventCollector->clear();
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->domainEventCollector->dispatch();
    }
}
