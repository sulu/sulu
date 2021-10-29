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
    /**
     * @param string|array<string, string> $resourceTitle
     * @param mixed[] $restoreData
     * @param mixed[] $restoreOptions
     */
    public function create(
        string $resourceKey,
        string $resourceId,
        $resourceTitle,
        array $restoreData,
        ?string $restoreType,
        array $restoreOptions,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): TrashItemInterface;

    public function add(TrashItemInterface $trashItem): void;

    public function remove(TrashItemInterface $trashItem): void;

    /**
     * @param mixed[] $criteria
     */
    public function findOneBy(array $criteria): ?TrashItemInterface;

    /**
     * @param mixed[] $criteria
     */
    public function getOneBy(array $criteria): TrashItemInterface;
}
