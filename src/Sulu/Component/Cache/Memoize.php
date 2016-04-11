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

use Doctrine\Common\Cache\Cache;

/**
 * Memoizer which uses Doctrine Cache to save data.
 */
class Memoize implements MemoizeInterface
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var int
     */
    protected $defaultLifeTime;

    /**
     * Constructor.
     */
    public function __construct(Cache $cache, $defaultLifeTime)
    {
        $this->cache = $cache;
        $this->defaultLifeTime = $defaultLifeTime;
    }

    /**
     * {@inheritdoc}
     */
    public function memoize($compute, $lifeTime = null)
    {
        // used to get information of the caller
        // returns a callstack (0 is current function, 1 is caller function)
        $callers = debug_backtrace();

        if (
            !isset($callers[1]) ||
            !isset($callers[1]['class']) ||
            !isset($callers[1]['function']) ||
            !isset($callers[1]['args'])
        ) {
            throw new \InvalidArgumentException();
        }

        // build cache key
        $id = sprintf('%s::%s', $callers[1]['class'], $callers[1]['function']);

        return $this->memoizeById($id, $callers[1]['args'], $compute, $lifeTime);
    }

    /**
     * {@inheritdoc}
     */
    public function memoizeById($id, $arguments, $compute, $lifeTime = null)
    {
        // determine lifetime
        if ($lifeTime === null) {
            $lifeTime = $this->defaultLifeTime;
        }

        // determine cache key
        $id = md5(sprintf('%s(%s)', $id, serialize($arguments)));

        // memoize pattern: save result for arguments once and
        // return the value from cache if it is called more than once
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        } else {
            $value = call_user_func_array($compute, $arguments);
            $this->cache->save($id, $value, $lifeTime);

            return $value;
        }
    }
}
