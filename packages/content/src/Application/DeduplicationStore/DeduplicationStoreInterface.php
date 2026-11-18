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

namespace Sulu\Content\Application\DeduplicationStore;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Holds the ids of the resources shown in the current request, grouped by resource key.
 */
interface DeduplicationStoreInterface extends ResetInterface
{
    public function add(string $resourceKey, string|int ...$ids): void;

    /**
     * @return string[]
     */
    public function getIds(string $resourceKey): array;
}
