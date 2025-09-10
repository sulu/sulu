<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\ReferenceStore;

interface ReferenceStoreInterface
{
    public function add(string $id, string $resourceKey): void;

    /**
     * @return array<string>
     */
    public function getAll(): array;
}
