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
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\HttpFoundation\Response;

class CacheLifetimeEnhancer implements CacheLifetimeEnhancerInterface
{
    /**
     * @var CacheLifetimeResolverInterface
     */
    private $cacheLifetimeResolver;

    /**
     * @var int
     */
    private $maxAge;

    /**
     * @var int
     */
    private $sharedMaxAge;

    /**
     * @var CacheLifetimeRequestStore
     */
    private $cacheLifetimeRequestStore;

    public function __construct(
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        $maxAge,
        $sharedMaxAge,
        CacheLifetimeRequestStore $cacheLifetimeRequestStore
    ) {
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->cacheLifetimeRequestStore = $cacheLifetimeRequestStore;
    }

    public function enhance(Response $response, StructureInterface $structure)
    {
        if (!\method_exists($structure, 'getCacheLifeTime')) {
            return;
        }

        $cacheLifetimeData = $structure->getCacheLifeTime();
        $cacheLifetime = $this->cacheLifetimeResolver->resolve(
            $cacheLifetimeData['type'],
            $cacheLifetimeData['value']
        );

        $requestCacheLifetime = $this->cacheLifetimeRequestStore->getCacheLifetime();

        if (null !== $requestCacheLifetime && $requestCacheLifetime < $cacheLifetime) {
            $cacheLifetime = $requestCacheLifetime;
        }

        // when structure cache-lifetime disabled - return
        if (0 === $cacheLifetime) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge($this->maxAge);
        $response->setSharedMaxAge($this->sharedMaxAge);

        // set reverse-proxy TTL (Symfony HttpCache, Varnish, ...) to avoid caching of intermediate proxies
        $response->headers->set(SuluHttpCache::HEADER_REVERSE_PROXY_TTL, $cacheLifetime);
    }
}
