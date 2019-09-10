<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Entity;

use Psr\Cache\CacheItemPoolInterface;

class CollaborationRepository
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var int
     */
    private $threshold;

    public function __construct(CacheItemPoolInterface $cache, int $threshold)
    {
        $this->cache = $cache;
        $this->threshold = $threshold;
    }

    /**
     * @return Collaboration[]
     */
    public function update(Collaboration $collaboration): array
    {
        $cacheItem = $this->cache->getItem($collaboration->getResourceKey() . '_' . $collaboration->getId());
        $value = $cacheItem->get() ?? [];
        $value[$collaboration->getConnectionId()] = $collaboration;
        $cacheItem->set($value);

        $value = array_filter($value, function(Collaboration $collaboration) {
            return $collaboration->getChanged() > time() - $this->threshold;
        });

        $this->cache->save($cacheItem);

        return array_values($value);
    }
}
