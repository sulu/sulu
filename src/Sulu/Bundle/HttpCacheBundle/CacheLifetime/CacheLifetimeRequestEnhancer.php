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

use Symfony\Component\HttpFoundation\RequestStack;

class CacheLifetimeRequestEnhancer
{
    const ATTRIBUTE_KEY = '_cacheLifetime';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function setCacheLifetime(int $cacheLifetime)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return;
        }

        if ($cacheLifetime >= 0
            && (
                !$request->attributes->has(static::ATTRIBUTE_KEY)
                || $request->attributes->get(static::ATTRIBUTE_KEY) > $cacheLifetime
            )
        ) {
            $request->attributes->set(static::ATTRIBUTE_KEY, $cacheLifetime);
        }
    }

    public function getCacheLifetime(): ?int
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return $request->attributes->get(static::ATTRIBUTE_KEY);
    }
}
