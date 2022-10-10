<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\HttpCacheBundle\Cache\CacheManager;
use Sulu\Bundle\WebsiteBundle\Cache\CacheClearer;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CacheClearerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    private $filesystem;

    /**
     * @var ObjectProphecy<RequestStack>
     */
    private $requestStack;

    /**
     * @var ObjectProphecy<Request>
     */
    private $request;

    /**
     * @var ObjectProphecy<EventDispatcherInterface>
     */
    private $eventDispatcher;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
    }

    public function testInvalidateTags(): void
    {
        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(true);
        $cacheManager->invalidateTag('webspace-sulu')->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer($cacheManager->reveal());
        $cacheClearer->clear(['webspace-sulu']);
    }

    public function testInvalidateDomain(): void
    {
        $this->requestStack->getCurrentRequest()
            ->willReturn($this->request->reveal())
            ->shouldBeCalled();

        $this->request->getHost()
            ->willReturn('sulu.io')
            ->shouldBeCalled();

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsInvalidate()->willReturn(true);
        $cacheManager->invalidateDomain('sulu.io')->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer($cacheManager->reveal());
        $cacheClearer->clear();
    }

    public function testInvalidateDomainWithoutTags(): void
    {
        $this->requestStack->getCurrentRequest()
            ->willReturn($this->request->reveal())
            ->shouldBeCalled();

        $this->request->getHost()
            ->willReturn('sulu.io')
            ->shouldBeCalled();

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsInvalidate()->willReturn(true);
        $cacheManager->invalidateDomain('sulu.io')->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer($cacheManager->reveal());
        $cacheClearer->clear();
    }

    public function testClear(): void
    {
        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsClear()->willReturn(true);
        $cacheManager->clear()->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer($cacheManager->reveal());
        $cacheClearer->clear(['webspace-sulu']);
    }

    public function testClearWithoutRequest(): void
    {
        $this->requestStack->getCurrentRequest()
            ->willReturn(null)
            ->shouldBeCalled();

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsInvalidate()->willReturn(true);
        $cacheManager->supportsClear()->willReturn(true);
        $cacheManager->clear()->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer($cacheManager->reveal());
        $cacheClearer->clear(['webspace-sulu']);
    }

    public function testRemoveDirectory(): void
    {
        $expectedPath = __DIR__ . '/var/cache/common/test/http_cache';

        $this->filesystem->exists($expectedPath)->willReturn(true)->shouldBeCalled();
        $this->filesystem->rename(Argument::cetera())->shouldBeCalled();
        $this->filesystem->remove(Argument::cetera())->shouldBeCalled();

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsInvalidate()->willReturn(false);
        $cacheManager->supportsClear()->willReturn(false);

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer();
        $cacheClearer->clear();
    }

    public function testRemoveDirectoryNotExist(): void
    {
        $expectedPath = __DIR__ . '/var/cache/common/test/http_cache';

        $this->filesystem->exists($expectedPath)->willReturn(false)->shouldBeCalled();
        $this->filesystem->rename(Argument::cetera())->shouldNotBeCalled();
        $this->filesystem->remove(Argument::cetera())->shouldNotBeCalled();

        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->supportsTags()->willReturn(false);
        $cacheManager->supportsInvalidate()->willReturn(false);
        $cacheManager->supportsClear()->willReturn(false);

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer();
        $cacheClearer->clear();
    }

    private function createCacheClearer(?CacheManager $cacheManager = null): CacheClearer
    {
        return new CacheClearer(
            $this->filesystem->reveal(),
            'test',
            $this->requestStack->reveal(),
            $this->eventDispatcher->reveal(),
            __DIR__ . '/var',
            $cacheManager
        );
    }
}
