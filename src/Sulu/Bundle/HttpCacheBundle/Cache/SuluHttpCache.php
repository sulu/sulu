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
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
    public function __construct(HttpKernelInterface $kernel, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->addSubscriber(new CustomTtlListener(static::HEADER_REVERSE_PROXY_TTL));
        $this->addSubscriber(new PurgeListener());

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
}
