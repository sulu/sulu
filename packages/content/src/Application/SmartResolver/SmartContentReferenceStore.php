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

namespace Sulu\Content\Application\SmartResolver;

/**
 * Collects the ids resolved by the smart content resolver during a single request, grouped by provider
 * type. It is used to implement the "exclude_duplicates" smart content option: a smart content block can
 * exclude the items that were already resolved by previous blocks of the same type.
 *
 * The store is reset between requests via the "kernel.reset" tag.
 *
 * @internal
 */
class SmartContentReferenceStore
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $ids = [];

    public function add(string $type, string|int $id): void
    {
        $this->ids[$type][(string) $id] = true;
    }

    /**
     * @return list<string>
     */
    public function getAll(string $type): array
    {
        // array_keys() coerces numeric string keys (e.g. entity ids) back to integers, so cast them
        // back to strings to honor the "excluded" filter contract (string[]).
        return \array_map(strval(...), \array_keys($this->ids[$type] ?? []));
    }

    public function reset(): void
    {
        $this->ids = [];
    }
}
