<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\FormatCache;

use Sulu\Bundle\MediaBundle\Media\Exception\CacheNotFoundException;

/**
 * Format cache clearer.
 */
class FormatCacheClearer implements FormatCacheClearerInterface
{
    /**
     * @var FormatCacheInterface[]
     */
    private $caches = [];

    /**
     * Clear all or the given cache.
     *
     * @param string $cache The alias of the cache
     *
     * @throws CacheNotFoundException if the cache is not found
     */
    public function clear($cache = null)
    {
        if (null !== $cache) {
            if (!array_key_exists($cache, $this->caches)) {
                throw new CacheNotFoundException($cache);
            }

            $this->caches[$cache]->clear();
        } else {
            foreach ($this->caches as $cache) {
                $cache->clear();
            }
        }
    }

    /**
     * Adds a cache to the aggregate.
     *
     * @param FormatCacheInterface $cache The cache
     * @param string $alias The cache alias
     */
    public function add(FormatCacheInterface $cache, $alias)
    {
        $this->caches[$alias] = $cache;
    }
}
