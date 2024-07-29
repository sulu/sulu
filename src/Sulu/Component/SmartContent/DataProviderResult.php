<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

/**
 * Contains results of smart content DataProvider.
 */
class DataProviderResult
{
    /**
     * @param ResourceItemInterface[] $items
     * @param bool $hasNextPage
     */
    public function __construct(
        private array $items,
        private $hasNextPage
    ) {
    }

    /**
     * @return bool
     */
    public function getHasNextPage()
    {
        return $this->hasNextPage;
    }

    /**
     * @return ResourceItemInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }
}
