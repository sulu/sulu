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

use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache as AbstractHttpCache;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Sulu HttpCache - Lookups a valid Response from the cache for the given request
 * or forwards the Request to the backend and stores the Response in the cache.
 */
class HttpCache extends AbstractHttpCache
{
    const HEADER_REVERSE_PROXY_TTL = 'X-Reverse-Proxy-TTL';

    const TARGET_GROUP_URL = '/_sulu_target_group';

    const TARGET_GROUP_HEADER = 'X-Sulu-Target-Group';

    const TARGET_GROUP_COOKIE = '_svtg';

    const TARGET_GROUP_COOKIE_LIFETIME = 2147483647;

    const VISITOR_SESSION_COOKIE = '_svs';

    const USER_CONTEXT_URL_HEADER = 'X-Forwarded-URL';

    /**
     * @var bool
     */
    private $hasAudienceTargeting;

    /**
     * @param HttpKernelInterface $kernel
     * @param bool $hasAudienceTargeting
     * @param string $cacheDir
     */
    public function __construct(HttpKernelInterface $kernel, $hasAudienceTargeting = false, $cacheDir = null)
    {
        parent::__construct($kernel, $cacheDir);

        $this->hasAudienceTargeting = $hasAudienceTargeting;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $hadValidTargetGroupCookie = null;
        if ($this->hasAudienceTargeting) {
            $hadValidTargetGroupCookie = $this->setTargetGroupHeader($request);
        }

        $response = parent::handle($request, $type, $catch);

        if (!$this->kernel->isDebug()) {
            $response->headers->remove(self::HEADER_REVERSE_PROXY_TTL);
        }

        if ($this->hasAudienceTargeting && !$hadValidTargetGroupCookie) {
            $this->setTargetGroupCookie($response, $request);
        }

        if ($this->hasAudienceTargeting && in_array(static::TARGET_GROUP_HEADER, $response->getVary())) {
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
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
     * Sets the target group header based on an existing cookie, so that the application can adapt the content according
     * to it. If the cookie didn't exist yet, another request is fired in order to set the value for the cookie.
     *
     * Returns true if the cookie was already set and false otherwise.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function setTargetGroupHeader(Request $request)
    {
        $hadValidTargetGroup = true;
        $visitorTargetGroup = $request->cookies->get(static::TARGET_GROUP_COOKIE);
        $visitorSession = $request->cookies->get(static::VISITOR_SESSION_COOKIE);

        if (null === $visitorTargetGroup || null === $visitorSession) {
            $hadValidTargetGroup = false;
            $visitorTargetGroup = $this->requestTargetGroup($request, $visitorTargetGroup);
        }

        if ($request->isMethodSafe()) {
            // add the target group as separate header to vary on it
            $request->headers->set(static::TARGET_GROUP_HEADER, (string) $visitorTargetGroup);
        }

        return $hadValidTargetGroup;
    }

    /**
     * Sends a request to the application to determine the target group of the current visitor.
     *
     * @param Request $request
     * @param int $currentTargetGroup
     *
     * @return string
     */
    private function requestTargetGroup(Request $request, $currentTargetGroup)
    {
        $targetGroupRequest = Request::create(
            static::TARGET_GROUP_URL,
            Request::METHOD_GET,
            [],
            [],
            [],
            $request->server->all()
        );

        if ($currentTargetGroup) {
            $targetGroupRequest->headers->set(static::TARGET_GROUP_HEADER, $currentTargetGroup);
        }

        $targetGroupRequest->headers->set(static::USER_CONTEXT_URL_HEADER, $request->getUri());

        // use the parent class to avoid target group based caching
        $targetGroupResponse = parent::handle($targetGroupRequest);

        return $targetGroupResponse->headers->get(static::TARGET_GROUP_HEADER);
    }

    /**
     * Set the cookie for the target group from the request. Should only be set in case the cookie was not set before.
     *
     * @param Response $response
     * @param Request $request
     */
    private function setTargetGroupCookie(Response $response, Request $request)
    {
        $response->headers->setCookie(
            new Cookie(
                static::TARGET_GROUP_COOKIE,
                $request->headers->get(static::TARGET_GROUP_HEADER),
                static::TARGET_GROUP_COOKIE_LIFETIME
            )
        );

        $response->headers->setCookie(
            new Cookie(
                static::VISITOR_SESSION_COOKIE,
                time()
            )
        );
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
