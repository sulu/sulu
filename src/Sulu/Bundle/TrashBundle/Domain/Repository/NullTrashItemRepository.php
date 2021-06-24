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

namespace Sulu\Bundle\TrashBundle\Domain\Repository;

use Sulu\Bundle\TrashBundle\Domain\Exception\TrashItemNotFoundException;
use Sulu\Bundle\TrashBundle\Domain\Factory\TrashItemFactoryInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

class NullTrashItemRepository implements TrashItemRepositoryInterface
{
    /**
     * @var TrashItemFactoryInterface
     */
    private $trashItemFactory;

    public function __construct(TrashItemFactoryInterface $trashItemFactory)
    {
        $this->trashItemFactory = $trashItemFactory;
    }

    /**
     * @param mixed[] $restoreData
     */
    public function create(
        string $resourceKey,
        array $restoreData,
        string $resourceTitle,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): TrashItemInterface {
        return $this->trashItemFactory->create(
            $resourceKey,
            $restoreData,
            $resourceTitle,
            $resourceSecurityContext,
            $resourceSecurityObjectType,
            $resourceSecurityObjectId
        );
    }

    public function addAndCommit(TrashItemInterface $trashItem): void
    {
    }

    public function removeAndCommit(TrashItemInterface $trashItem): void
    {
    }

    public function findOneBy(array $criteria): ?TrashItemInterface
    {
        return null;
    }

    public function getOneBy(array $criteria): TrashItemInterface
    {
        throw new TrashItemNotFoundException($criteria);
    }
}
