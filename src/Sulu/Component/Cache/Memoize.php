<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Memoizer which uses Doctrine Cache to save data.
 */
class Memoize implements MemoizeInterface, ResetInterface
{
    /**
     * @param ArrayAdapter $cache should also include FlushableCache to reset in other runtimes like FrankenPHP correctly
     * @param int $defaultLifeTime
     */
    public function __construct(protected ArrayAdapter $cache, protected $defaultLifeTime)
    {
    }

    public function memoizeById($id, $arguments, $compute, $lifeTime = null)
    {
        // determine lifetime
        if (null === $lifeTime) {
            $lifeTime = $this->defaultLifeTime;
        }

        // determine cache key
        $id = \md5(\sprintf('%s(%s)', $id, \serialize($arguments)));

        return $this->cache->get($id, function($item) use ($compute, $arguments, $lifeTime) {
            $value = \call_user_func_array($compute, $arguments);

            $item->expiresAfter($lifeTime);

            return $value;
        });
    }

    /**
     * @return void
     */
    public function reset()
    {
        // This is not necessary as the cache flushes itself on reset.
        $this->cache->clear();
    }
}
