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
 * Handlers implementing this interface should "flush" their underlying proxy.
 *
 * Flush should be called in the kernel terminate event therefore this method
 * should also be used to do time sensative operations such as sending invalidation
 * requests to the proxy server.
 */
interface HandlerFlushInterface extends HandlerInterface
{
    /**
     * Flush the underlying cache mechanism.
     *
     * NOTE: Currently this could be quite inefficient as there is currently
     *       no way to track which handlers use which implementations.
     *
     * @return bool True for success null or false if not implemented
     */
    public function flush();
}
