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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestEnhancer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CacheLifetimeRequestEnhancerTest extends TestCase
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CacheLifetimeRequestEnhancer
     */
    private $cacheLifetimeRequestEnhancer;

    public function setUp(): void
    {
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->cacheLifetimeRequestEnhancer = new CacheLifetimeRequestEnhancer($this->requestStack->reveal());
    }

    public function provideSetCacheLifetime()
    {
        return [
            [null, 200, 200],
            [400, 100, 100],
            [300, 700, 300],
            [null, -500, null],
        ];
    }

    /**
     * @dataProvider provideSetCacheLifetime
     */
    public function testSetCacheLifetime($previousCacheLifetime, $newCacheLifetime, $expectedCacheLifetime)
    {
        $request = new Request([], [], $previousCacheLifetime ? ['_cacheLifetime' => $previousCacheLifetime] : []);
        $this->requestStack->getCurrentRequest()->willReturn($request);

        $this->cacheLifetimeRequestEnhancer->setCacheLifetime($newCacheLifetime);

        $this->assertEquals($expectedCacheLifetime, $this->cacheLifetimeRequestEnhancer->getCacheLifetime());
    }

    public function testGetCacheLifetimeWithoutRequest()
    {
        $this->assertNull($this->cacheLifetimeRequestEnhancer->getCacheLifetime());
    }
}
