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

namespace Sulu\Component\Rest\Exception;

interface DependantResourcesFoundExceptionInterface extends RestExceptionInterface
{
    /**
     * @return array<int, array<array{id: int|string, resourceKey: string}>>
     */
    public function getDependantResources(): array;

    public function getTotalDependantResources(): int;
}
