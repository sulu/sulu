<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

final class TrashItemRepository implements TrashItemRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository<TrashItemInterface>
     */
    private $entityRepository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->entityRepository = $entityManager->getRepository(TrashItemInterface::class);
    }

    public function addAndCommit(TrashItemInterface $trashItem): void
    {
        $this->entityManager->persist($trashItem);
        $this->entityManager->flush();
    }

    public function removeAndCommit(TrashItemInterface $trashItem): void
    {
        $this->entityManager->remove($trashItem);
        $this->entityManager->flush();
    }

    public function findOneBy(array $criteria): ?TrashItemInterface
    {
        return $this->entityRepository->findOneBy($criteria);
    }

    public function getOneBy(array $criteria): TrashItemInterface
    {
        $trashItem = $this->findOneBy($criteria);

        if (null === $trashItem) {
            throw new TrashItemNotFoundException($criteria);
        }

        return $trashItem;
    }
}
