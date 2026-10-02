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

use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own event subscriber / listener instead.
 */
class ErrorPageCacheClearEventSubscriber implements EventSubscriberInterface
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
