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

namespace Sulu\Content\Application\ContentResolver;

/**
 * Collects the resource ids referenced while resolving a single request, grouped by resource key.
 *
 * It is used to implement the smart content "exclude_duplicates" option: a smart content block can
 * exclude the resources that were already referenced by earlier selections or smart content blocks
 * of the same resource key.
 *
 * The tracker is request-wide and shared across the main request and its subrequests. It is reset only
 * after the complete request via the "kernel.reset" tag, so the whole render tree participates in the
 * same deduplication scope. It does not know anything about smart content or filters.
 *
 * @internal
 */
class ContentDeduplicationTracker
{
    /**
     * @var array<string, array<string, true>>
     */
    private array $ids = [];

    public function add(string $resourceKey, string|int $id): void
    {
        $this->ids[$resourceKey][(string) $id] = true;
    }

    /**
     * @return list<string>
     */
    public function getAll(string $resourceKey): array
    {
        // array_keys() coerces numeric string keys (e.g. entity ids) back to integers, so cast them
        // back to strings to honor the "excluded" filter contract (string[]).
        return \array_map(strval(...), \array_keys($this->ids[$resourceKey] ?? []));
    }

    public function reset(): void
    {
        $this->ids = [];
    }
}