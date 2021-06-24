<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TrashBundle\Domain\Factory;

use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;

interface TrashItemFactoryInterface
{
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
    ): TrashItemInterface;
}
