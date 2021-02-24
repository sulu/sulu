<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DomainEventBundle\Collector;

use Sulu\Bundle\DomainEventBundle\Entity\DomainEvent;

interface DoctrineDomainEventCollectorInterface
{
    public function collect(DomainEvent $domainEvent): void;
}
