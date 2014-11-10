<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Doctrine\Common\Cache\Cache;

/**
 * Memoizer which uses Doctrine Cache to save data
 */
class Memoize implements MemoizeInterface
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * Constructor
     */
    function __construct(Cache $cache)
    {
        $this->cache = $cache;
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
    public function memoizeById($id, $parameter, $compute, $lifeTime = null)
    {
        $id = md5(sprintf('%s(%s)', $id, serialize($parameter)));

        // memoize pattern: save result for arguments once and
        // return the value from cache if it is called more than once
        if ($this->cache->contains($id)) {
            return $this->cache->fetch($id);
        } else {
            $value = call_user_func_array($compute, $parameter);
            $this->cache->save($id, $value, $lifeTime);

            return $value;
        }
    }
} 
