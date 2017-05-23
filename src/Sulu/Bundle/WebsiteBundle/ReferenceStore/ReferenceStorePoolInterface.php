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
 * Interface for reference-store pool.
 */
interface ReferenceStorePoolInterface
{
    /**
     * Returns reference-stores.
     *
     * @return ReferenceStoreInterface[]
     */
    public function getStores();

    /**
     * Returns reference-store for given alias.
     *
     * @param string $alias
     *
     * @return ReferenceStoreInterface
     */
    public function getStore($alias);
}
