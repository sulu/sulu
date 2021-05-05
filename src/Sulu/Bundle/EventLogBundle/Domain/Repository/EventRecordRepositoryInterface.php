<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\EventLogBundle\Domain\Repository;

use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;
use Sulu\Bundle\EventLogBundle\Domain\Model\EventRecordInterface;

interface EventRecordRepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface;

    public function addAndCommit(EventRecordInterface $eventRecord): void;
}
