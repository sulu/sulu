<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache;

use FOS\HttpCache\SymfonyCache\EventDispatchingHttpCache;
use FOS\HttpCache\SymfonyCache\UserContextSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\Esi;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Sulu HttpCache - Lookups a valid Response from the cache for the given request
 * or forwards the Request to the backend and stores the Response in the cache.
 */
class HttpCache extends EventDispatchingHttpCache
{
    const HEADER_REVERSE_PROXY_TTL = 'X-Reverse-Proxy-TTL';

    const USER_HASH_URI = '/_user_context';

    const USER_HASH_HEADER = 'X-User-Context-Hash';

    const SESSION_NAME_PREFIX = 'user-context';

    public function __construct(HttpKernelInterface $kernel, $hasUserContext = false, $cacheDir = null)
    {
        parent::__construct(
            $kernel,
            new Store($cacheDir ?: $kernel->getCacheDir() . '/http_cache'),
            new Esi(),
            ['debug' => $kernel->isDebug()]
        );

        if ($hasUserContext) {
            $this->addSubscriber(new UserContextSubscriber([
                'user_hash_header' => static::USER_HASH_HEADER,
                'user_hash_uri' => static::USER_HASH_URI,
                'session_name_prefix' => static::SESSION_NAME_PREFIX,
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $response = parent::handle($request, $type, $catch);

        if (!$this->getKernel()->isDebug()) {
            $response->headers->remove(self::HEADER_REVERSE_PROXY_TTL);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function invalidate(Request $request, $catch = false)
    {
        if ('PURGE' !== $request->getMethod()) {
            return parent::invalidate($request, $catch);
        }

        $response = new Response();
        if ($this->getStore()->purge($request->getUri())) {
            $response->setStatusCode(200, 'Purged');
        } else {
            $response->setStatusCode(200, 'Not purged');
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function isFreshEnough(Request $request, Response $entry)
    {
        if (!$entry->isFresh() && !$this->isFreshCacheEntry($entry)) {
            return $this->lock($request, $entry);
        }

        return true;
    }

    /**
     * Returns true if the cache entry is "fresh".
     *
     * @param Response $entry
     *
     * @return bool
     */
    private function isFreshCacheEntry(Response $entry)
    {
        return $this->getReverseProxyTtl($entry) > 0;
    }

    /**
     * Returns the response reverse-proxy cache TTL in seconds.
     *
     * @param Response $response
     *
     * @return int|null The TTL in seconds
     */
    private function getReverseProxyTtl(Response $response)
    {
        if (null !== $maxAge = $response->headers->get(self::HEADER_REVERSE_PROXY_TTL)) {
            return $maxAge - $response->getAge();
        }

        return;
    }
}
