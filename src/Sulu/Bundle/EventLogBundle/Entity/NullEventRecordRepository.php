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

class NullEventRecordRepository implements EventRecordRepositoryInterface
{
    /**
     * @var string
     */
    private $eventRecordClass;

    public function __construct(string $eventRecordClass)
    {
        $this->eventRecordClass = $eventRecordClass;
    }

    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface
    {
        return new $this->eventRecordClass();
    }

    public function add(EventRecordInterface $eventRecord): void
    {
    }

    public function commit(): void
    {
    }
}
