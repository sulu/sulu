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
use Sulu\Component\Persistence\Repository\RepositoryInterface;

interface EventRecordRepositoryInterface extends RepositoryInterface
{
    public function createForDomainEvent(DomainEvent $domainEvent): EventRecordInterface;
}
