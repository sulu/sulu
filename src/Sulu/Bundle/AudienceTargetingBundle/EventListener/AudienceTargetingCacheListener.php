<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AudienceTargetingBundle\EventListener;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use FOS\HttpCache\SymfonyCache\CacheInvalidation;
use FOS\HttpCache\SymfonyCache\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listen to the cache events, sets the needed headers and cookies for audience targeting.
 */
class AudienceTargetingCacheListener implements EventSubscriberInterface
{
    const TARGET_GROUP_URL = '/_sulu_target_group';

    const TARGET_GROUP_HEADER = 'X-Sulu-Target-Group';

    const TARGET_GROUP_COOKIE = '_svtg';

    const TARGET_GROUP_COOKIE_LIFETIME = 2147483647;

    const VISITOR_SESSION_COOKIE = '_svs';

    const USER_CONTEXT_URL_HEADER = 'X-Forwarded-URL';

    protected $hadValidTargetGroupCookie = false;

    /**
     * @param CacheEvent $cacheEvent
     */
    public function preHandle(CacheEvent $cacheEvent)
    {
        if (self::TARGET_GROUP_URL === $cacheEvent->getRequest()->getRequestUri()) {
            return;
        }

        $this->hadValidTargetGroupCookie = $this->setTargetGroupHeader(
            $cacheEvent->getRequest(),
            $cacheEvent->getKernel()
        );
    }

    /**
     * @param CacheEvent $cacheEvent
     */
    public function postHandle(CacheEvent $cacheEvent)
    {
        if (self::TARGET_GROUP_URL === $cacheEvent->getRequest()->getRequestUri()) {
            return;
        }

        $response = $cacheEvent->getResponse();
        $request = $cacheEvent->getRequest();

        if (!$this->hadValidTargetGroupCookie) {
            $this->setTargetGroupCookie($response, $request);
        }

        if (in_array(static::TARGET_GROUP_HEADER, $response->getVary())) {
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
        }
    }

    /**
     * Sets the target group header based on an existing cookie, so that the application can adapt the content according
     * to it. If the cookie didn't exist yet, another request is fired in order to set the value for the cookie.
     *
     * Returns true if the cookie was already set and false otherwise.
     *
     * @param Request $request
     * @param CacheInvalidation $kernel
     *
     * @return bool
     */
    private function setTargetGroupHeader(Request $request, CacheInvalidation $kernel)
    {
        $hadValidTargetGroup = true;
        $visitorTargetGroup = $request->cookies->get(static::TARGET_GROUP_COOKIE);
        $visitorSession = $request->cookies->get(static::VISITOR_SESSION_COOKIE);

        if (null === $visitorTargetGroup || null === $visitorSession) {
            $hadValidTargetGroup = false;
            $visitorTargetGroup = $this->requestTargetGroup($request, $kernel, $visitorTargetGroup);
        }

        if ($request->isMethodCacheable()) {
            // add the target group as separate header to vary on it
            $request->headers->set(static::TARGET_GROUP_HEADER, (string) $visitorTargetGroup);
        }

        return $hadValidTargetGroup;
    }

    /**
     * Sends a request to the application to determine the target group of the current visitor.
     *
     * @param Request $request
     * @param CacheInvalidation $kernel
     * @param null|int $currentTargetGroup
     *
     * @return string
     */
    private function requestTargetGroup(Request $request, CacheInvalidation $kernel, int $currentTargetGroup = null)
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

        $targetGroupResponse = $kernel->handle($targetGroupRequest);

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
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PRE_HANDLE => ['preHandle', 512],
            Events::POST_HANDLE => ['postHandle', -512],
        ];
    }
}
