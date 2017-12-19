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

interface CacheManagerInterface
{
    /**
     * Invalidates given path with given headers.
     */
    public function invalidatePath(string $path, array $headers = []);

    /**
     * Invalidates given tag.
     */
    public function invalidateTag(string $tag);

    /**
     * Invalidates whole domain via BAN method.
     */
    public function invalidateDomain(string $domain);

    /**
     * Returns true if current proxy client supports invalidation.
     */
    public function supportsInvalidate();
}
