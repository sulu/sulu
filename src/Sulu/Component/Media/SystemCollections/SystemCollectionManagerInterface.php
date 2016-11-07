<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SystemCollections;

/**
 * Interface for system collection manager.
 */
interface SystemCollectionManagerInterface
{
    const COLLECTION_TYPE = 'collection.system';

    const COLLECTION_KEY = 'system_collections';

    /**
     * Builds cache for system collections.
     */
    public function warmUp();

    /**
     * Returns id of system collection with given key.
     *
     * @param string $key
     *
     * @return int id of system collection
     */
    public function getSystemCollection($key);

    /**
     * Returns true if given id is registered as system collection.
     *
     * @param int $id
     *
     * @returns bool
     */
    public function isSystemCollection($id);
}
