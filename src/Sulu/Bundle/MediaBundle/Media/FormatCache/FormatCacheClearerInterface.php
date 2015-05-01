<?php
namespace Sulu\Bundle\MediaBundle\Media\FormatCache;

use Sulu\Bundle\MediaBundle\Media\Exception\CacheNotFoundException;

/**
 * Format cache clearer.
 */
interface FormatCacheClearerInterface
{
    /**
     * Clear all or the given cache.
     *
     * @param string $cache The alias of the cache
     *
     * @throws CacheNotFoundException if the cache is not found
     */
    public function clear($cache = null);
}
