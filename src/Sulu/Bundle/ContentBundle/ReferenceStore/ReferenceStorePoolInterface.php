<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\ReferenceStore;

/**
 * Interface for reference-store pool.
 */
interface ReferenceStorePoolInterface
{
    /**
     * Returns reference-store for given alias.
     *
     * @param string $alias
     *
     * @return ReferenceStoreInterface
     */
    public function getStore($alias);

    /**
     * Returns existing references in all pools.
     *
     * @return Reference[]
     */
    public function getReferences();
}
