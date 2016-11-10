<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Tests\Unit;

use Cron\CronExpression;
use Sulu\Component\HttpCache\CacheLifetimeResolver;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;

class CacheLifetimeResolverTest extends \PHPUnit_Framework_TestCase
{
    public function testSupportWrongType()
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();

        $this->assertFalse($cacheLifetimeResolver->supports('test', '10'));
    }

    public function testSupportSeconds()
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();

        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, '10'));
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 10));
        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 0));
        $this->assertFalse($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_SECONDS, 'asdf'));
    }

    public function testSupportExpression()
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();

        $this->assertTrue($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '@daily'));
        $this->assertTrue(
            $cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, '0 0 1 1 *')
        );
        $this->assertFalse($cacheLifetimeResolver->supports(CacheLifetimeResolverInterface::TYPE_EXPRESSION, 'asdf'));
    }

    public function testResolveSeconds()
    {
        $cacheLifetimeResolver = new CacheLifetimeResolver();

        $this->assertEquals(10, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, '10'));
        $this->assertEquals(10, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 10));
        $this->assertEquals(0, $cacheLifetimeResolver->resolve(CacheLifetimeResolverInterface::TYPE_SECONDS, 0));
    }

    public function testResolveExpression()
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
