<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle;

/**
 * Events thrown by the WebsiteBundle.
 */
final class Events
{
    /**
     * Will be raised after http caches have been cleared.
     */
    const CACHE_CLEAR = 'sulu_website.cache_clear';
}
