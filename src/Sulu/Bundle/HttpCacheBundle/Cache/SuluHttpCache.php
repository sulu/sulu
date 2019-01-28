<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\Cache;

use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\CustomTtlListener;
use FOS\HttpCache\SymfonyCache\DebugListener;
use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\PurgeListener;
use FOS\HttpCache\SymfonyCache\PurgeTagsListener;
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Toflar\Psr6HttpCacheStore\Psr6Store;

/**
 * Abstract class to extend from when using Symfony cache.
 * Add needed subscriber in the constructor.
 */
class SuluHttpCache extends HttpCache implements CacheInvalidation
{
    use EventDispatchingHttpCache;

    const HEADER_REVERSE_PROXY_TTL = 'X-Reverse-Proxy-TTL';

    /**
     * @param HttpKernelInterface $kernel
     * @param string $cacheDir
     */
    public function __construct(SuluKernel $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new CustomTtlListener(static::HEADER_REVERSE_PROXY_TTL));
        $this->addSubscriber(new PurgeListener());
        $this->addSubscriber(new PurgeTagsListener());

        if ($kernel->isDebug()) {
            $this->addSubscriber(new DebugListener());
        }
    }

    /**
     * Made public to allow event listeners to do refresh operations.
     *
     * {@inheritdoc}
     */
    public function fetch(Request $request, $catch = false)
    {
        return parent::fetch($request, $catch);
    }

    protected function createStore()
    {
        if (!$this->kernel instanceof SuluKernel) {
            throw new \RuntimeException('Unexpected kernel instance given');
        }

        return new Psr6Store([
            'cache_directory' => $this->kernel->getHttpCacheDir(),
            'cache_tags_header' => TagHeaderFormatter::DEFAULT_HEADER_NAME,
        ]);
    }
}
