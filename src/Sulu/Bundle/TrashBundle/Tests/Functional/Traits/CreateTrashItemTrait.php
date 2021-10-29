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

namespace Sulu\Bundle\TrashBundle\Tests\Functional\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;

trait CreateTrashItemTrait
{
    /**
     * @param mixed[] $restoreData
     * @param mixed[] $restoreOptions
     * @param string|array<string, string> $resourceTitle
     */
    protected static function createTrashItem(
        string $resourceKey = 'test_resource',
        string $resourceId = '1',
        $resourceTitle = '',
        array $restoreData = [],
        ?string $restoreType = null,
        array $restoreOptions = [],
        ?string $resourceSecurityContext = null,
        ?string $resourceSecurityObjectType = null,
        ?string $resourceSecurityObjectId = null
    ): TrashItemInterface {
        $trashItem = static::getTrashItemRepository()->create(
            $resourceKey,
            $resourceId,
            $resourceTitle,
            $restoreData,
            $restoreType,
            $restoreOptions,
            $resourceSecurityContext,
            $resourceSecurityObjectType,
            $resourceSecurityObjectId
        );

        $trashItem->setUser(static::getTestUser());

        static::getTrashItemRepository()->add($trashItem);
        static::getEntityManager()->flush();

        return $trashItem;
    }

    /**
     * @return UserInterface
     */
    abstract protected static function getTestUser();

    abstract protected static function getTrashItemRepository(): TrashItemRepositoryInterface;

    abstract protected static function getEntityManager(): EntityManagerInterface;
}
