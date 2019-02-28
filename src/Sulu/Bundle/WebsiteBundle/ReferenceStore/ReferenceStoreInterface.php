<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\ReferenceStore;

/**
 * Interface for reference-stores.
 */
interface ReferenceStoreInterface
{
    /**
     * Add id.
     *
     * @param mixed $id
     */
    public function add($id);

    /**
     * Returns references.
     *
     * @return array
     */
    public function getAll();
}
