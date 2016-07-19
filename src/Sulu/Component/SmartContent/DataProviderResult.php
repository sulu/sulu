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
     * @var array
     */
    private $referencedUuids;

    public function __construct(array $items, $hasNextPage, array $referencedUuids = [])
    {
        $this->hasNextPage = $hasNextPage;
        $this->items = $items;
        $this->referencedUuids = $referencedUuids;
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

    /**
     * @return array
     */
    public function getReferencedUuids()
    {
        return $this->referencedUuids;
    }
}
