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

use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

interface TrashItemRepositoryInterface
{
    public function addAndCommit(TrashItemInterface $trashItem): void;

    public function removeAndCommit(TrashItemInterface $trashItem): void;

    /**
     * @param mixed[] $criteria
     */
    public function findOneBy(array $criteria): ?TrashItemInterface;

    /**
     * @param mixed[] $criteria
     */
    public function getOneBy(array $criteria): TrashItemInterface;
}
