<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
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
     * @var bool
     */
    private $hasNextPage;

    /**
     * @var ResourceItemInterface[]
     */
    private $items;

    /**
     * @param array $items
     * @param bool $hasNextPage
     */
    public function __construct(array $items, $hasNextPage)
    {
        $this->items = $items;
        $this->hasNextPage = $hasNextPage;
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
