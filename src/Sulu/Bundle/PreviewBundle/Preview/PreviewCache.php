<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Preview;

use Doctrine\Common\Cache\Cache;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal BC Layer between doctrine and symfony cache
 */
class PreviewCache
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @param CacheItemPoolInterface|Cache $cache
     */
    public function __construct($cache)
    {
        if ($cache instanceof Cache) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.1',
                'To inject $cache as instance of "%s" is deprecated, use a "%s" instead.',
                \get_class($cache),
                CacheItemPoolInterface::class
            );
        }

        if (!$cache instanceof Cache && !$cache instanceof CacheItemPoolInterface) {
            throw new \RuntimeException(
                \sprintf(
                    'The $cache need to be an instance of "%s" or "%s" but got "%s".',
                    CacheItemPoolInterface::class,
                    Cache::class,
                    \get_class($cache)
                )
            );
        }

        $this->cache = $cache;
    }

    public function delete(string $id): void
    {
        $this->cache->delete($id);
    }

    public function contains(string $id): bool
    {
        if ($this->cache instanceof Cache) {
            return $this->cache->contains($id);
        }

        return $this->cache->hasItem($id);
    }

    public function save(string $id, string $value, int $expires = 0): void
    {
        if ($this->cache instanceof Cache) {
            $this->cache->save($id, $value, $expires);

            return;
        }

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($id);
        $cacheItem->set($value);

        if ($expires) {
            $cacheItem->expiresAfter($expires);
        }

        $this->cache->save($cacheItem);
    }

    public function fetch(string $id): string
    {
        if ($this->cache instanceof Cache) {
            return $this->cache->fetch($id);
        }

        /** @var CacheItemInterface $cacheItem */
        $cacheItem = $this->cache->getItem($id);

        return $cacheItem->get();
    }
}
