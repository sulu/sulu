<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;

/**
 * Class AccountListener.
 */
class AccountListener
{
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof AccountInterface) {
            $entityManager = $args->getEntityManager();
            // after saving account check if number is set, else set a new one
            if ($entity->getNumber() === null) {
                $entity->setNumber(sprintf('%05d', $entity->getId()));
                $entityManager->flush();
            }
        }
    }
}
