<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use FOS\HttpCache\SymfonyCache\CacheEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentCacheListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentCacheListenerTests extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SegmentCacheListener
     */
    private $segmentCacheListener;

    public function setUp(): void
    {
        $this->segmentCacheListener = new SegmentCacheListener();
    }

    public function providePreHandleCookieValue()
    {
        return [
            ['s'],
            ['w'],
            [null],
        ];
    }

    /**
     * @dataProvider providePreHandleCookieValue
     */
    public function testPreHandleCookieValue($cookieValue): void
    {
        $request = new Request([], [], [], ['_ss' => $cookieValue]);
        $response = new Response();

        $this->segmentCacheListener->preHandle($this->getCacheEvent($request, $response));

        $this->assertEquals($cookieValue, $request->headers->get('X-Sulu-Segment'));
    }

    public function providePostHandleVary()
    {
        return [
            ['X-Something', 60, 120, 120],
            ['X-Sulu-Segment', 60, 120, 0],
            [null, 60, 120, 120],
        ];
    }

    /**
     * @dataProvider providePostHandleVary
     */
    public function testPostHandleWithVary($header, $maxAge, $sharedMaxAge, $expectedMaxAge): void
    {
        $request = new Request();
        $response = new Response();

        $response->setMaxAge($maxAge);
        $response->setSharedMaxAge($sharedMaxAge);

        if ($header) {
            $response->setVary($header);
        }

        $this->segmentCacheListener->postHandle($this->getCacheEvent($request, $response));

        $this->assertEquals($expectedMaxAge, $response->getMaxAge());
    }

    private function getCacheEvent(Request $request, Response $response): CacheEvent
    {
        $httpCache = $this->prophesize(SuluHttpCache::class);

        return new CacheEvent(
            $httpCache->reveal(),
            $request,
            $response
        );
    }
}
