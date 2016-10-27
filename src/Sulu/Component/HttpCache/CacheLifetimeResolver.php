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

    /**
     * {@inheritdoc}
     */
    public function resolve($type, $value)
    {
        $cacheLifetimeKey = sprintf('%s:%s', $type, $value);

        if (!array_key_exists($cacheLifetimeKey, $this->cacheLifetimes)) {
            switch ($type) {
                case self::TYPE_EXPRESSION:
                    $this->cacheLifetimes[$cacheLifetimeKey] = $this->getCacheLifetimeForExpression($value);
                    break;
                case self::TYPE_SECONDS:
                    $this->cacheLifetimes[$cacheLifetimeKey] = (int) $value;
                    break;
                default:
                    $this->cacheLifetimes[$cacheLifetimeKey] = 0;
            }
        }

        return $this->cacheLifetimes[$cacheLifetimeKey];
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type, $value)
    {
        if (!in_array($type, self::$types)) {
            return false;
        }

        if (self::TYPE_EXPRESSION === $type) {
            return CronExpression::isValidExpression($value);
        }

        return is_numeric($value);
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
