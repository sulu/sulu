<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\CacheLifetime;

use Cron\CronExpression;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;

class CacheLifetimeResolverTest extends TestCase
{
    public function testSupportWrongType(): void
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->assertFalse($cacheLifetimeResolver->supports('test', '10'));
    }

    public function testSupportSeconds(): void
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, '10'));
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 10));
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 0));
        $this->assertFalse($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 'asdf'));
    }

    public function testSupportExpression(): void
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '@daily'));
        $this->assertTrue(
            $cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '0 0 1 1 *')
        );
        $this->assertFalse($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, 'asdf'));
    }

    public function testResolveSeconds(): void
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();
        $this->assertEquals(10, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, '10'));
        $this->assertEquals(10, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 10));
        $this->assertEquals(0, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 0));
    }

    public function testResolveExpression(): void
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();
        $now = new \DateTime();
        $this->assertLessThanOrEqual(
            CronExpression::factory('@daily')->getNextRunDate()->getTimestamp() - $now->getTimestamp(),
            $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '@daily')
        );
        $this->assertLessThanOrEqual(
            CronExpression::factory('0 0 1 1 *')->getNextRunDate()->getTimestamp() - $now->getTimestamp(),
            $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '0 0 1 1 *')
        );
    }
}
