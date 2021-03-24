<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Application\Collector;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

interface DomainEventCollectorInterface
{
    public function collect(DomainEvent $domainEvent): void;
}
