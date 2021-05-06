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

use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

interface DocumentDomainEventCollectorInterface
{
    public function collect(DomainEvent $domainEvent): void;

    public function clear(): void;

    public function dispatch(): void;
}
