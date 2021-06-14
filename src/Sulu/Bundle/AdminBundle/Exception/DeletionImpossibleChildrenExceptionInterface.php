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

namespace Sulu\Bundle\AdminBundle\Exception;

interface DeletionImpossibleChildrenExceptionInterface extends \Throwable
{
    public const EXCEPTION_CODE = 12345; // TODO change

    /**
     * @return array<int, array<array{id: int|string, resourceKey: string}>>
     */
    public function getChildResources(): array;

    public function getTotalChildResources(): int;
}
