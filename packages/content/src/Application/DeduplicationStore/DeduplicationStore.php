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

class DeduplicationStore implements DeduplicationStoreInterface
{
    /**
     * @var array<string, array<int|string, string>>
     */
    private array $ids = [];

    public function add(string $resourceKey, string|int ...$ids): void
    {
        foreach ($ids as $id) {
            if ('' !== $id) {
                $this->ids[$resourceKey][$id] = (string) $id;
            }
        }
    }

    public function getIds(string $resourceKey): array
    {
        return \array_values($this->ids[$resourceKey] ?? []);
    }

    public function reset(): void
    {
        $this->ids = [];
    }
}
