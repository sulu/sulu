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
