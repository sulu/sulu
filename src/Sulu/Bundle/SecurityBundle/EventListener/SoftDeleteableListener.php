<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;

/**
 * Deletes user after deleting contact.
 */
class SoftDeleteableListener implements EventSubscriber
{
    /**
     * @var string
     */
    private $userEntityName;

    public function __construct($userEntityName)
    {
        $this->userEntityName = $userEntityName;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return ['postSoftDelete'];
    }

    /**
     * Called after soft-delete.
     *
     * @param LifecycleEventArgs $eventArgs
     */
    public function postSoftDelete(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $entityManager = $eventArgs->getEntityManager();

        if ($entity instanceof Contact) {
            /** @var UserRepository $repository */
            $repository = $entityManager->getRepository($this->userEntityName);
            $user = $repository->findUserByContact($entity->getId());

            if (null !== $user) {
                $entityManager->remove($user);
                $entityManager->flush();
            }
        }
    }
}
