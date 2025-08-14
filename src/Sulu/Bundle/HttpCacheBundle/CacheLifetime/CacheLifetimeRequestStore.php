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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CacheLifetimeRequestStore
{
    public const ATTRIBUTE_KEY = '_cacheLifetime';

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function setCacheLifetime(int $cacheLifetime): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
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

        if (!$request instanceof Request) {
            return null;
        }

        return $request->attributes->get(static::ATTRIBUTE_KEY);
    }
}
