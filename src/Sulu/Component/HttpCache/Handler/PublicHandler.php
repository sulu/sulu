<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\Handler;

use Sulu\Component\Content\Compat\PageInterface;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\HttpCache\CacheLifetimeResolverInterface;
use Sulu\Component\HttpCache\HandlerUpdateResponseInterface;
use Sulu\Component\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set standard cache settings on the response.
 * Includes the TTL of the structure.
 */
class PublicHandler implements HandlerUpdateResponseInterface
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
     * @var bool
     */
    private $usePageTtl;

    /**
     * @param CacheLifetimeResolverInterface $cacheLifetimeResolver
     * @param int $maxAge Cache max age in seconds
     * @param int $sharedMaxAge Cache shared max age in seconds
     * @param bool $usePageTtl Use page TTL
     */
    public function __construct(
        CacheLifetimeResolverInterface $cacheLifetimeResolver,
        $maxAge = 240,
        $sharedMaxAge = 960,
        $usePageTtl = true
    ) {
        $this->cacheLifetimeResolver = $cacheLifetimeResolver;
        $this->maxAge = $maxAge;
        $this->sharedMaxAge = $sharedMaxAge;
        $this->usePageTtl = $usePageTtl;
    }

    /**
     * {@inheritdoc}
     */
    public function updateResponse(Response $response, StructureInterface $structure)
    {
        if (!$structure instanceof PageInterface) {
            return;
        }

        $cacheLifetimeData = $structure->getCacheLifeTime();
        $cacheLifetime = $this->cacheLifetimeResolver->resolve(
            $cacheLifetimeData['type'],
            $cacheLifetimeData['value']
        );

        // when structure cache-lifetime disabled - return
        if (0 === $cacheLifetime) {
            return;
        }

        // mark the response as either public or private
        $response->setPublic();

        // set the private and shared max age
        $response->setMaxAge($this->maxAge);
        $response->setSharedMaxAge($this->sharedMaxAge);

        $proxyTtl = $this->usePageTtl ?
            $response->getAge() + $cacheLifetime :
            $response->getAge();

        // set reverse-proxy TTL (Symfony HttpCache, Varnish, ...)
        $response->headers->set(HttpCache::HEADER_REVERSE_PROXY_TTL, $proxyTtl);
    }
}
