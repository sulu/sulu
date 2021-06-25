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
     * @param string|array<string, string> $resourceTitle
     */
    public function create(
        string $resourceKey,
        string $resourceId,
        array $restoreData,
        $resourceTitle,
        ?string $resourceSecurityContext,
        ?string $resourceSecurityObjectType,
        ?string $resourceSecurityObjectId
    ): TrashItemInterface;
}
