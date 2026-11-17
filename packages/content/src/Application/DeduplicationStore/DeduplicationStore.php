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

class DeduplicationStore implements DeduplicationStoreInterface, ResetInterface
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $ids = [];

    public function add(string $id, string $resourceKey): void
    {
        $this->ids[$resourceKey][$id] = true;
    }

    public function getIds(string $resourceKey): array
    {
        // array keys turn numeric strings into integers
        return \array_map(\strval(...), \array_keys($this->ids[$resourceKey] ?? []));
    }

    public function reset(): void
    {
        $this->ids = [];
    }
}
