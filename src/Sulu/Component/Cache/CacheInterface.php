<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

/**
 * Interface for cache.
 */
interface CacheInterface
{
    /**
     * Read content of cache.
     *
     * @return \Serializable
     */
    public function read();

    /**
     * Write data into cache.
     *
     * @param \Serializable|\Serializable[] $data
     *
     * @throws \RuntimeException When cache file can't be written
     */
    public function write($data);

    /**
     * Invalidate cache.
     *
     * @throws \RuntimeException When cache file can't be invalidated
     */
    public function invalidate();

    /**
     * Checks if the cache is still fresh.
     *
     * This method always returns true when debug is off and the
     * cache file exists.
     *
     * @return bool true if the cache is fresh, false otherwise
     */
    public function isFresh();
}
