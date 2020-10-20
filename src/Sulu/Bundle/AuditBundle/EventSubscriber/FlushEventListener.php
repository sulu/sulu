<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AuditBundle\EventSubscriber;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Sulu\Bundle\AuditBundle\Helper\EventMap;
use Sulu\Bundle\AuditBundle\Service\TrailServiceInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FlushEventListener
{
    /**
     * @var TrailServiceInterface
     */
    private $trailService;
    /**
     * @var string|\Stringable|\Symfony\Component\Security\Core\User\UserInterface
     */
    private $loginUser;

    /**
     * FlushEventListener constructor.
     */
    public function __construct(
        TrailServiceInterface $trailService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->trailService = $trailService;

        if ($tokenStorage->getToken()) {
            $this->loginUser = $tokenStorage->getToken()->getUser();
        }
    }

    /**
     * @throws \ReflectionException
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        if ($this->loginUser) {
            foreach ($uow->getScheduledEntityInsertions() as $key => $entity) {
                $trail = $this->trailService->createTrailByNameAndChanges(
                    null,
                    $uow->getEntityPersister(\get_class($entity))->getClassMetadata()->getName(),
                    $uow->getEntityChangeSet($entity),
                    EventMap::INSERT
                );
                $this->save($em, $uow, $trail);
            }

            foreach ($uow->getScheduledEntityUpdates() as $entity) {
                $trail = $this->trailService->createTrailByNameAndChanges(
                    $entity->getId(),
                    $uow->getEntityPersister(\get_class($entity))->getClassMetadata()->getName(),
                    $uow->getEntityChangeSet($entity),
                    EventMap::UPDATE
                );
                $this->save($em, $uow, $trail);
            }

            foreach ($uow->getScheduledEntityDeletions() as $entity) {
                $trail = $this->trailService->createTrailByNameAndChanges(
                    $entity->getId(),
                    $uow->getEntityPersister(\get_class($entity))->getClassMetadata()->getName(),
                    $uow->getEntityChangeSet($entity),
                    EventMap::DELETE
                );
                $this->save($em, $uow, $trail);
            }
        }
    }

    /**
     * @param $entity
     */
    public function save(EntityManager $em, UnitOfWork $uow, $entity)
    {
        $em->persist($entity);
        $metaData = $em->getClassMetadata(\get_class($entity));
        $uow->computeChangeSet($metaData, $entity);
    }
}
