<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserTwoFactor;

/**
 * @internal
 */
class ForceTwoFactorSubscriber implements EventSubscriber
{
    private string $twoFactorForcePattern;

    public function __construct(string $twoFactorForcePattern)
    {
        $this->twoFactorForcePattern = $twoFactorForcePattern;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
            Events::prePersist,
        ];
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $this->handleTwoFactorForce($event);
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->handleTwoFactorForce($event);
    }

    private function handleTwoFactorForce(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if (!$entity instanceof User) {
            return;
        }

        if (!\preg_match($this->twoFactorForcePattern, $entity->getEmail() ?: '')) {
            return;
        }

        $twoFactor = $entity->getTwoFactor();

        if (!$twoFactor) {
            $twoFactor = new UserTwoFactor($entity);
            $event->getEntityManager()->persist($twoFactor);
        }

        if (!$twoFactor->getMethod()) {
            $twoFactor->setMethod('email');
        }
    }
}
