<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\HttpCacheBundle\CacheLifetime;

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Symfony\Component\HttpFoundation\Response;

class CacheLifetimeEnhancer implements CacheLifetimeEnhancerInterface
{
    public function __construct(
        private CacheLifetimeRequestStore $cacheLifetimeRequestStore,
        private int $maxAge,
        private int $sharedMaxAge,
    ) {
    }

    public function enhance(Response $response): void
    {
        $cacheLifetime = $this->cacheLifetimeRequestStore->getCacheLifetime();

        if (!$cacheLifetime) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge($this->maxAge);
        $response->setSharedMaxAge($this->sharedMaxAge);

        // set reverse-proxy TTL (Symfony HttpCache, Varnish, ...) to avoid caching of intermediate proxies
        $response->headers->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, (string) $cacheLifetime);
    }
}
