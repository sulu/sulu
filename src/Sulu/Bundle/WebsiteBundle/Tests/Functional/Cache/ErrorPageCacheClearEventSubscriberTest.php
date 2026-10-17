<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;
use Sulu\Bundle\WebsiteBundle\EventListener\ErrorPageCacheClearEventSubscriber;
use Sulu\Bundle\WebsiteBundle\Events;

class ErrorPageCacheClearEventSubscriberTest extends KernelTestCase
{
    public function testSubscribedEventsHasCorrectMapping(): void
    {
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $listener = new ErrorPageCacheClearEventSubscriber($cacheMock);

        $events = $listener::getSubscribedEvents();

        $this->assertArrayHasKey(Events::CACHE_CLEAR, $events);
        $this->assertEquals('onCacheClear', $events[Events::CACHE_CLEAR]);
    }

    public function testOnCacheClearClearsPool(): void
    {
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $eventMock = $this->createMock(CacheClearEvent::class);

        $cacheMock->expects($this->once())->method('clear');

        $listener = new ErrorPageCacheClearEventSubscriber($cacheMock);
        $listener->onCacheClear($eventMock);
    }
}
