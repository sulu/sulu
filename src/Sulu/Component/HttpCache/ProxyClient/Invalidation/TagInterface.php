<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\HttpCache\ProxyClient\Invalidation;

use FOS\HttpCache\ProxyClient\ProxyClientInterface;

/**
 * Indicates that the proxy client supports explicit tag invalidation.
 *
 * TODO: This interface is already available in the master branch of the
 *       FOSHttpCache component, but as there is no release at time of writing
 *       we are implementing it seprately here.
 *
 *       See: https://github.com/FriendsOfSymfony/FOSHttpCache/issues/283
 */
interface TagInterface extends ProxyClientInterface
{
    /**
     * Remove/Expire cache objects based on cache tags.
     *
     * @param array $tags Tags that should be removed/expired from the cache
     *
     * @return $this
     */
    public function invalidateTags(array $tags);
}
