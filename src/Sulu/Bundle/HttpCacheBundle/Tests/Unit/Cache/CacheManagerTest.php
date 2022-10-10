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
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;

class CacheManagerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ObjectProphecy<FOSCacheManager>
     */
    private $fosCacheManager;

    public function setUp(): void
    {
        $this->fosCacheManager = $this->prophesize(FOSCacheManager::class);

        $this->cacheManager = new CacheManager($this->fosCacheManager->reveal());
    }

    public function testSupportsInvalidate(): void
    {
        $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)->willReturn(true);
        $this->assertTrue($this->cacheManager->supportsInvalidate());
    }

    public function testSupportsTags(): void
    {
        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->assertTrue($this->cacheManager->supportsTags());
    }

    public function testInvalidateTag(): void
    {
        $tag = '1234-1234-1234';

        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(false);
        $this->fosCacheManager->invalidateTags([$tag])->shouldNotBeCalled();
        $this->cacheManager->invalidateTag($tag);

        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags([$tag])->shouldBeCalled();
        $this->cacheManager->invalidateTag($tag);
    }

    public function testInvalidatePath(): void
    {
        $this->fosCacheManager->supports(FOSCacheManager::PATH)->willReturn(true);
        $this->fosCacheManager->invalidatePath('http://sulu.lo/test', Argument::cetera())->shouldBeCalled();

        $this->fosCacheManager->supports(FOSCacheManager::REFRESH)->willReturn(true);
        $this->fosCacheManager->refreshPath('http://sulu.lo/test', Argument::cetera())->shouldBeCalled();

        $this->cacheManager->invalidatePath('http://sulu.lo/test');
    }

    public function testInvalidatePathWithoutSupport(): void
    {
        $this->fosCacheManager->supports(FOSCacheManager::PATH)->willReturn(false);
        $this->fosCacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();
        $this->cacheManager->invalidatePath('http://sulu.lo/test');
    }

    public function testInvalidateDomain(): void
    {
        $domain = 'sulu.io';

        $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)->willReturn(false);
        $this->fosCacheManager->invalidatePath(Argument::any())->shouldNotBeCalled();
        $this->cacheManager->invalidateDomain($domain);

        $this->fosCacheManager->supports(FOSCacheManager::INVALIDATE)->willReturn(true);
        $this->fosCacheManager->invalidateRegex(
            BanCapable::REGEX_MATCH_ALL,
            BanCapable::CONTENT_TYPE_ALL,
            $domain
        )->shouldBeCalled();
        $this->cacheManager->invalidateDomain($domain);
    }

    public function testInvalidateReference(): void
    {
        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags(['test-1'])->shouldBeCalled();

        $this->cacheManager->invalidateReference('test', 1);
    }

    public function testInvalidateUuidReference(): void
    {
        $tag = '72a31676-282d-11e8-b467-0ed5f89f718b';

        $this->fosCacheManager->supports(FOSCacheManager::TAGS)->willReturn(true);
        $this->fosCacheManager->invalidateTags([$tag])->shouldBeCalled();

        $this->cacheManager->invalidateReference('test', $tag);
    }
}
