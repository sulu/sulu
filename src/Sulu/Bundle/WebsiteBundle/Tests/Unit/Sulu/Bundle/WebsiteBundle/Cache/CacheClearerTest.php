<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Cache;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
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
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CacheManager|null
     */
    private $cacheManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->cacheManager = $this->prophesize(CacheManager::class);
        $this->requestStack = $this->prophesize(RequestStack::class);
        $this->request = $this->prophesize(Request::class);
        $this->filesystem = $this->prophesize(Filesystem::class);
    }

    public function testClearOnFilesystem(): void
    {
        $expectedPath = __DIR__ . '/var/cache/common/test/http_cache';

        $this->filesystem->exists($expectedPath)->willReturn(true)->shouldBeCalled();
        $this->filesystem->remove($expectedPath)->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer();
        $cacheClearer->clear();
    }

    public function testClearOnFilesystemNotExist(): void
    {
        $expectedPath = __DIR__ . '/var/cache/common/test/http_cache';

        $this->filesystem->exists($expectedPath)->willReturn(false)->shouldBeCalled();
        $this->filesystem->remove($expectedPath)->shouldNotBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer();
        $cacheClearer->clear();
    }

    public function testClearOnCacheManagerNoRequest(): void
    {
        $this->requestStack->getCurrentRequest()->shouldBeCalled();

        $this->cacheManager->supportsInvalidate()->willReturn(true);
        $this->cacheManager->invalidateDomain(Argument::any())->shouldNotBeCalled();

        $cacheClearer = $this->createCacheClearer(true);
        $cacheClearer->clear();
    }

    public function testClearOnCacheManagerWithRequest(): void
    {
        $this->requestStack->getCurrentRequest()
            ->willReturn($this->request->reveal())
            ->shouldBeCalled();

        $this->request->getHost()
            ->willReturn('sulu.io')
            ->shouldBeCalled();

        $this->cacheManager->supportsInvalidate()->willReturn(true);
        $this->cacheManager->invalidateDomain('sulu.io')->shouldBeCalled();

        $this->eventDispatcher->dispatch(
            Argument::type(CacheClearEvent::class),
            Events::CACHE_CLEAR
        )->shouldBeCalled();

        $cacheClearer = $this->createCacheClearer(true);
        $cacheClearer->clear();
    }

    private function createCacheClearer(bool $withCacheManager = false): CacheClearer
    {
        $cacheManager = null;

        if ($withCacheManager) {
            $cacheManager = $this->cacheManager->reveal();
        }

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
