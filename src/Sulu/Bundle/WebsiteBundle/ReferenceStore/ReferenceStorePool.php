<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

/**
 * Combines other reference-stores.
 */
class ReferenceStorePool implements ReferenceStorePoolInterface
{
    /**
     * @var ReferenceStoreInterface[]
     */
    private $stores = [];

    /**
     * @param ReferenceStoreInterface[] $stores
     */
    public function __construct(array $stores)
    {
        $this->stores = $stores;
    }

    /**
     * {@inheritdoc}
     */
    public function getStores()
    {
        return $this->stores;
    }

    /**
     * {@inheritdoc}
     */
    public function getStore($alias)
    {
        if (!array_key_exists($alias, $this->stores)) {
            throw new ReferenceStoreNotExistsException($alias, array_keys($this->stores));
        }

        return $this->stores[$alias];
    }
}
