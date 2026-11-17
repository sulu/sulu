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

/**
 * Holds the ids of the resources shown in the current request, grouped by resource key.
 *
 * Selections add the ids they show, smart content providers exclude them when "exclude_duplicates" is set
 * and add their own results, so a resource is not shown twice on the same page.
 */
interface DeduplicationStoreInterface
{
    public function add(string $id, string $resourceKey): void;

    /**
     * @return string[]
     */
    public function getIds(string $resourceKey): array;
}
