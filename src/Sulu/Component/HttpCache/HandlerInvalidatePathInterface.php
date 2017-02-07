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

/**
 * Handlers implementing this will invalidate the given path with the
 * caching proxy server.
 *
 * Note that this is called during the request therefore the task of this
 * interfaces method should normally be to record paths which should later
 * be invalidated in the `flush` interface.
 */
interface HandlerInvalidatePathInterface
{
    /**
     * Invalidate a path or URL.
     *
     * @param string $path    Path or URL
     * @param array  $headers HTTP headers (optional)
     *
     * @return $this
     */
    public function invalidatePath($path, array $headers = []);
}
