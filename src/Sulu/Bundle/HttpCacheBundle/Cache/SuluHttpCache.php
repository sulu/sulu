<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Cache;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CleanupCacheTagsListener;
use FOS\HttpCache\SymfonyCache\CustomTtlListener;
use FOS\HttpCache\SymfonyCache\DebugListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentCacheListener;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

/**
 * Abstract class to extend from when using Symfony cache.
 * Add needed subscriber in the constructor.
 */
class SuluHttpCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    public const HEADER_REVERSE_PROXY_TTL = 'X-Reverse-Proxy-TTL';

    /**
     * @param string $cacheDir
     */
    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        if (!$cacheDir && $kernel instanceof SuluKernel) {
            $cacheDir = $kernel->getCommonCacheDir() . \DIRECTORY_SEPARATOR . 'http_cache';
        }

        parent::__construct($kernel, $cacheDir);

        foreach ($this->getSubscribers() as $subscriber) {
            $this->addSubscriber($subscriber);
        }
    }

    /**
     * @return EventSubscriberInterface[]
     */
    protected function getSubscribers(): array
    {
        $subscribers = [
            CustomTtlListener::class => new CustomTtlListener(static::HEADER_REVERSE_PROXY_TTL, $this->kernel->isDebug()),
            PurgeListener::class => new PurgeListener(),
            PurgeTagsListener::class => new PurgeTagsListener(),
            SegmentCacheListener::class => new SegmentCacheListener(),
        ];

        if ($this->kernel->isDebug()) {
            $subscribers[DebugListener::class] = new DebugListener();
        } else {
            $subscribers[CleanupCacheTagsListener::class] = new CleanupCacheTagsListener();
        }

        return $subscribers;
    }

    /**
     * Made public to allow event listeners to do refresh operations.
     *
     * {@inheritdoc}
     */
    public function fetch(Request $request, $catch = false): Response
    {
        return parent::fetch($request, $catch);
    }

    protected function createStore(): StoreInterface
    {
        return new Psr6Store([
            'cache_directory' => $this->cacheDir,
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
        ]);
    }
}
