<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

/**
 * A cache lifetime resolver should resolve the given cache lifetime metadata based on the type
 * and should return an absolute cache lifetime in seconds.
 */
interface CacheLifetimeResolverInterface
{
    /**
     * Cache lifetime in seconds.
     */
    const TYPE_SECONDS = 'seconds';

    /**
     * Cache lifetime as cron expression.
     */
    const TYPE_EXPRESSION = 'expression';

    /**
     * Get cache lifetime in seconds.
     *
     * @param string $type
     * @param mixed $value
     *
     * @return int Cache lifetime in seconds
     */
    public function resolve($type, $value);

    /**
     * Returns true if combination of type and value is supported.
     *
     * @param string $type
     * @param mixed $value
     *
     * @return bool
     */
    public function supports($type, $value);
}
