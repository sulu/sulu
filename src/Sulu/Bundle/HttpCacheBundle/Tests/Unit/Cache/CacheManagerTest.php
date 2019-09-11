<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Tests\Unit\Cache;

use FOS\HttpCache\ProxyClient\Invalidation\BanCapable;
use FOS\HttpCacheBundle\CacheManager as FOSCacheManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Component\Webspace\Url\Replacer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CacheManagerTest extends TestCase
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var FOSCacheManager
     */
    private $fosCacheManager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Replacer
     */
    private $urlReplacer;

    public function setUp(): void
    {
        $this->fosCacheManager = $this->prophesize(FOSCacheManager::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->urlReplacer = new Replacer();

        $this->cacheManager = new CacheManager(
            $this->fosCacheManager->reveal(),
            $this->requestStack->reveal(),
            $this->urlReplacer
        );
    }

    public function testInvalidateTag()
    {
        $tag = '1234-1234-1234';

        // proxy client doesn't support tag invalidation
        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(false);
        $this->fosCacheManager->invalidateTags([$tag])->shouldNotBeCalled();
        $this->cacheManager->invalidateTag($tag);

        // proxy client supports tag invalidation
        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags([$tag])->shouldBeCalled();
        $this->cacheManager->invalidateTag($tag);
    }

    public function testInvalidatePathWithHostReplacer()
    {
        $path = 'http://{host}/test';

        // proxy client doesn't support path invalidation
        $this->fosCacheManager->supports(FOSCacheManager::PATH)->willReturn(false);
        $this->fosCacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();
        $this->cacheManager->invalidatePath($path);

        // proxy client supports path invalidation
        $this->fosCacheManager->supports(FOSCacheManager::PATH)->willReturn(true);
        $this->fosCacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();
        $this->cacheManager->invalidatePath($path);

        // set the host correctly
        $request = $this->prophesize(Request::class);
        $request->getHttpHost()->willReturn('sulu.lo');
        $this->requestStack->getCurrentRequest()->willReturn($request->reveal());

        $this->cacheManager = new CacheManager(
            $this->fosCacheManager->reveal(),
            $this->requestStack->reveal(),
            $this->urlReplacer
        );

        $this->fosCacheManager->supports(FOSCacheManager::PATH)->willReturn(true);
        $this->fosCacheManager->invalidatePath('http://sulu.lo/test', Argument::cetera())->shouldBeCalled();

        $this->fosCacheManager->supports(FOSCacheManager::REFRESH)->willReturn(true);
        $this->fosCacheManager->refreshPath('http://sulu.lo/test', Argument::cetera())->shouldBeCalled();

        $this->cacheManager->invalidatePath($path);
    }

    public function testInvalidateDomain()
    {
        $domain = 'sulu.io';

        // proxy client doesn't support ban invalidation
        $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)->willReturn(false);
        $this->fosCacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();
        $this->cacheManager->invalidateDomain($domain);

        // proxy client doesn't support ban invalidation
        $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)->willReturn(true);
        $this->fosCacheManager->invalidateRegex(
            BanCapable::REGEX_MATCH_ALL,
            BanCapable::CONTENT_TYPE_ALL,
            $domain
        )->shouldBeCalled();
        $this->cacheManager->invalidateDomain($domain);
    }

    public function testInvalidateReference()
    {
        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags(['test-1'])->shouldBeCalled();

        $this->cacheManager->invalidateReference('test', 1);
    }

    public function testInvalidateUuidReference()
    {
        $tag = '72a31676-282d-11e8-b467-0ed5f89f718b';

        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags([$tag])->shouldBeCalled();

        $this->cacheManager->invalidateReference('test', $tag);
    }
}
