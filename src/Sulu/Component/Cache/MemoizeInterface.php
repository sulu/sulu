<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

/**
 * Memoizer which uses Doctrine Cache to save data.
 */
interface MemoizeInterface
{
    /**
     * Returns the value stored in the cache or uses the passed function to compute the value and save to cache
     * This function uses the callstack to generate a unique key for the caching mechanism.
     *
     * @param callable $compute
     * @param int $lifeTime cache lifetime
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public function memoize($compute, $lifeTime = null);

    /**
     * Returns the value stored in the cache or uses the passed function to compute the value and save to cache
     * This function uses the given key for the caching mechanism.
     *
     * @param mixed $id
     * @param array $arguments array of parameter to call compute function
     * @param callable $compute
     * @param int $lifeTime cache lifetime
     *
     * @return mixed
     */
    public function memoizeById($id, $arguments, $compute, $lifeTime = null);
}
