<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\CacheLifetime;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\HttpCacheBundle\Cache\AbstractHttpCache;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeEnhancer;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolver;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CacheLifetimeEnhancerTest extends TestCase
{
    /**
     * @var CacheLifetimeEnhancer
     */
    private $cacheLifetimeEnhancer;

    /**
     * @var CacheLifetimeResolver
     */
    private $cacheLifetimeResolver;

    /**
     * @var PageBridge
     */
    private $page;

    /**
     * @var SnippetBridge
     */
    private $snippet;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ResponseHeaderBag
     */
    private $responseHeaderBag;

    /**
     * @var int
     */
    private $maxAge = 200;

    /**
     * @var int
     */
    private $sharedMaxAge = 300;

    public function setUp()
    {
        $this->cacheLifetimeResolver = $this->prophesize(CacheLifetimeResolver::class);
        $this->page = $this->prophesize(PageBridge::class);
        $this->snippet = $this->prophesize(SnippetBridge::class);
        $this->response = $this->prophesize(Response::class);
        $this->responseHeaderBag = $this->prophesize(ResponseHeaderBag::class);
        $this->response->headers = $this->responseHeaderBag;

        $this->cacheLifetimeEnhancer = new CacheLifetimeEnhancer(
            $this->cacheLifetimeResolver->reveal(),
            $this->maxAge,
            $this->sharedMaxAge
        );
    }

    public function provideCacheLifeTime()
    {
        return [
            [50],
            [500],
            [0],
        ];
    }

    /**
     * @param $cacheLifetime
     *
     * @dataProvider provideCacheLifeTime
     */
    public function testEnhance(int $cacheLifetime)
    {
        $this->page->getCacheLifeTime()->willReturn(
            ['type' => CacheLifetimeResolverInterface::TYPE_SECONDS, 'value' => $cacheLifetime]
        );

        if ($cacheLifetime > 0) {
            $this->responseHeaderBag->set(AbstractHttpCache::HEADER_REVERSE_PROXY_TTL, $cacheLifetime)->shouldBeCalled();
            $this->response->setPublic()->shouldBeCalled();
            $this->response->setMaxAge($this->maxAge)->shouldBeCalled();
            $this->response->setSharedMaxAge($this->sharedMaxAge)->shouldBeCalled();
        } else {
            $this->responseHeaderBag->set(Argument::cetera())->shouldNotBeCalled();
            $this->response->setPublic()->shouldNotBeCalled();
            $this->response->setMaxAge(Argument::any())->shouldNotBeCalled();
            $this->response->setSharedMaxAge(Argument::any())->shouldNotBeCalled();
        }

        $this->cacheLifetimeResolver->resolve(Argument::cetera())->willReturn($cacheLifetime);
        $this->cacheLifetimeEnhancer->enhance($this->response->reveal(), $this->page->reveal());
    }

    public function testEnhanceSnippet()
    {
        $this->cacheLifetimeEnhancer->enhance($this->response->reveal(), $this->snippet->reveal());
        $this->response->setPublic()->shouldNotBeCalled();
    }
}
