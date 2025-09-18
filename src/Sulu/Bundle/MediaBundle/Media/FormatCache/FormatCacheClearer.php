<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
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
     * @var array<string, FormatCacheInterface>
     */
    private $caches = [];

    /**
     * @param iterable<string, FormatCacheInterface> $caches
     */
    public function __construct(iterable $caches = [])
    {
        $this->caches = [...$caches];
    }

    public function clear($cache = null)
    {
        if (null !== $cache) {
            if (!\array_key_exists($cache, $this->caches)) {
                throw new CacheNotFoundException($cache);
            }

            $this->caches[$cache]->clear();
        } else {
            foreach ($this->caches as $cache) {
                $cache->clear();
            }
        }
    }
}
