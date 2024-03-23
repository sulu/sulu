<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\CacheLifetime;

use Cron\CronExpression;

/**
 * The cache lifetime resolver resolves the given cache lifetime metadata based on the type
 * and returns an absolute cache lifetime in seconds.
 */
class CacheLifetimeResolver implements CacheLifetimeResolverInterface
{
    /**
     * Cache lifetime types.
     *
     * @var array
     */
    protected static $types = [self::TYPE_SECONDS, self::TYPE_EXPRESSION];

    /**
     * Cached cache lifetimes.
     *
     * @var array
     */
    protected $cacheLifetimes = [];

    public function resolve($type, $value)
    {
        $cacheLifetimeKey = \sprintf('%s:%s', $type, $value);

        if (!\array_key_exists($cacheLifetimeKey, $this->cacheLifetimes)) {
            $this->cacheLifetimes[$cacheLifetimeKey] = match ($type) {
                self::TYPE_EXPRESSION => $this->getCacheLifetimeForExpression($value),
                self::TYPE_SECONDS => (int) $value,
                default => 0,
            };
        }

        return $this->cacheLifetimes[$cacheLifetimeKey];
    }

    public function supports($type, $value)
    {
        if (!\in_array($type, self::$types)) {
            return false;
        }

        if (self::TYPE_EXPRESSION === $type) {
            return CronExpression::isValidExpression($value);
        }

        return \is_numeric($value);
    }

    /**
     * @param string $expression Cron expression
     *
     * @return int Cache lifetime in seconds
     */
    protected function getCacheLifetimeForExpression($expression)
    {
        if (!CronExpression::isValidExpression($expression)) {
            return 0;
        }

        $now = new \DateTime();
        $cronExpression = CronExpression::factory($expression);
        $endTime = $cronExpression->getNextRunDate($now);

        return $endTime->getTimestamp() - $now->getTimestamp();
    }
}
