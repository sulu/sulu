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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\EventListener\CacheClearListener;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Sulu\Bundle\TestBundle\Testing\KernelTestCase;
use Sulu\Bundle\WebsiteBundle\Events;
use Sulu\Bundle\WebsiteBundle\Event\CacheClearEvent;

class ErrorCacheClearTest extends KernelTestCase
{
    public function testSubscribedEventsHasCorrectMapping(): void
    {
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $listener = new CacheClearListener($cacheMock);

        $events = $listener::getSubscribedEvents();

        $this->assertArrayHasKey(Events::CACHE_CLEAR, $events);
        $this->assertEquals('onCacheClear', $events[Events::CACHE_CLEAR]);
    }

    public function testOnCacheClearDoesNotClearPoolWhenDebugIsTrue(): void
    {
        $cacheMock = $this->createMock(CacheItemPoolInterface::class);
        $eventMock = $this->createMock(CacheClearEvent::class);

        $cacheMock->expects($this->never())->method('clear');
    }

    public function testListenerIsRegisteredInContainerInProduction(): void
    {
        $kernel = self::bootKernel([
            'debug' => true,
            'environment' => 'test',
        ]);

        $container = $kernel->getContainer();

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $container->get('event_dispatcher');
        $listeners = $eventDispatcher->getListeners(Events::CACHE_CLEAR);

        $hasCacheClearListener = false;
        foreach ($listeners as $listener) {
            if (is_array($listener) && $listener[0] instanceof CacheClearListener) {
                $hasCacheClearListener = true;
                break;
            }
        }

        $this->assertTrue(
            $hasCacheClearListener,
            'Failed asserting that CacheClearListener is registered to ' . Events::CACHE_CLEAR . ' when debug is false.'
        );
    }
}
