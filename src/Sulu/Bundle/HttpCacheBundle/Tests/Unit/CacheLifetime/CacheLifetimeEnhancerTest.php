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
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancer;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class CacheLifetimeEnhancerTest extends TestCase
{
    /**
     * @var CacheLifetimeEnhancer
     */
    private $cacheLifetimeEnhancer;

    /**
     * @var CacheLifetimeRequestStore
     */
    private $cacheLifetimeRequestStore;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var int
     */
    private $maxAge = 200;

    /**
     * @var int
     */
    private $sharedMaxAge = 300;

    public function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->cacheLifetimeRequestStore = new CacheLifetimeRequestStore($this->requestStack);

        $this->cacheLifetimeEnhancer = new CacheLifetimeEnhancer(
            $this->cacheLifetimeRequestStore,
            $this->maxAge,
            $this->sharedMaxAge,
        );
    }

    public static function provideCacheLifeTime()
    {
        yield [50, null, 50];
        yield [500, null, 500];
        yield [0, null, 0];
        yield [700, 800, 700];
        yield [600, 400, 400];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideCacheLifeTime')]
    public function testEnhance(int $cacheLifetime, ?int $requestCacheLifetime, int $expectedCacheLifetime): void
    {
        $this->requestStack->push(new Request());

        if (null !== $requestCacheLifetime) {
            $this->cacheLifetimeRequestStore->setCacheLifetime($requestCacheLifetime);
        }

        $this->cacheLifetimeRequestStore->setCacheLifetime($cacheLifetime);

        $response = new Response();
        $this->cacheLifetimeEnhancer->enhance($response);

        if ($expectedCacheLifetime > 0) {
            $this->assertTrue($response->isCacheable());
            $this->assertSame('max-age=200, public, s-maxage=300', $response->headers->get('Cache-Control'));
            $this->assertSame((string) $expectedCacheLifetime, $response->headers->get(SuluHttpCache::HEADER_REVERSE_PROXY_TTL));
        } else {
            $this->assertFalse($response->isCacheable());
            $this->assertFalse($response->headers->has(SuluHttpCache::HEADER_REVERSE_PROXY_TTL));
            $this->assertSame('no-cache, private', $response->headers->get('Cache-Control'));
        }
    }
}
