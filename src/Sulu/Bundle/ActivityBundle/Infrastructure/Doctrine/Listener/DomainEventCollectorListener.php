<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ActivityBundle\Infrastructure\Doctrine\Listener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;

class DomainEventCollectorListener
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

    public function onClear(OnClearEventArgs $args): void
    {
        $this->domainEventCollector->clear();
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->domainEventCollector->dispatch();
    }
}
