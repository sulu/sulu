<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Prophecy\Prophecy\ProphecySubjectInterface;
use Sulu\Bundle\EventLogBundle\Application\Subscriber\SetDomainEventUserSubscriber as BaseSetDomainEventUserSubscriber;
use Sulu\Bundle\EventLogBundle\Domain\Event\DomainEvent;

class SetDomainEventUserSubscriber extends BaseSetDomainEventUserSubscriber
{
    public function setDomainEventUser(DomainEvent $event): void
    {
        parent::setDomainEventUser($event);

        // Unset user, if it's a prophesized double
        if ($event->getUser() instanceof ProphecySubjectInterface) {
            $event->setUser(null);
        }
    }
}
