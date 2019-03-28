<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Cache;

/**
 * Events related to clearing caches.
 */
final class Events
{
    /**
     * Will be raised after http cache has been cleared.
     */
    const POST_CLEAR = 'sulu_website.cache.post_clear';
}
