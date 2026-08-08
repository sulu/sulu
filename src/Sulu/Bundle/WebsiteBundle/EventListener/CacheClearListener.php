<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\WebsiteBundle\Events;

class CacheClearListener implements EventSubscriberInterface
{
    public function __construct(private CacheItemPoolInterface $cache)
    {

    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::CACHE_CLEAR => 'onCacheClear',
        ];
    }

    public function onCacheClear(CacheClearEvent $event): void
    {
        $this->cache->clear();
    }
}
