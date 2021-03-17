<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Entity;

use Sulu\Bundle\EventLogBundle\Event\DomainEvent;

interface EventRecordRepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface;

    public function add(EventRecordInterface $eventRecord): void;

    public function commit(): void;
}
