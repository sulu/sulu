<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ReferenceBundle\Infrastructure\Doctrine\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Sulu\Bundle\ReferenceBundle\Domain\Model\ReferenceInterface;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;

class DoctrineReferenceSubscriber implements EventSubscriber
{
    /**
     * @var ReferenceRepositoryInterface
     */
    private $referenceRepository;

    public function __construct(ReferenceRepositoryInterface $referenceRepository)
    {
        $this->referenceRepository = $referenceRepository;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::preFlush,
        ];
    }

    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();

        $references = [];
        foreach ($entityManager->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof ReferenceInterface) {
                continue;
            }

            // TODO remove only from the same workflowStage
            $references[] = [
                'resourceKey' => $entity->getResourceKey(),
                'resourceId' => $entity->getResourceId(),
                'locale' => $entity->getLocale(),
            ];
        }

        $references = \array_unique($references, \SORT_REGULAR);
        foreach ($references as $referenceEntry) {
            $this->referenceRepository->removeByReferenceResourceKeyAndId(
                $referenceEntry['resourceKey'],
                $referenceEntry['resourceId'],
                $referenceEntry['locale']
            );
        }
    }
}
