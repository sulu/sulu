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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\EventListener\SegmentSubscriber;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Segment;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SegmentSubscriberTest extends TestCase
{
    /*
     * @var SegmentSubscriber
     */
    private $segmentSubscriber;

    public function setUp(): void
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->segmentSubscriber = new SegmentSubscriber('X-Sulu-Segment', $this->requestAnalyzer->reveal());
    }

    public function testAddVaryHeaderWithSegmentedWebspace()
    {
        $request = new Request();
        $response = new Response();

        $webspace = new Webspace();
        $webspace->addSegment(new Segment());
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);

        $this->segmentSubscriber->addVaryHeader($this->createResponseEvent($request, $response));

        $this->assertEquals(['X-Sulu-Segment'], $response->getVary());
    }

    public function testAddVaryHeaderWithoutSegmentedWebspace()
    {
        $request = new Request();
        $response = new Response();
        $webspace = new Webspace();
        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->segmentSubscriber->addVaryHeader($this->createResponseEvent($request, $response));

        $this->assertEquals([], $response->getVary());
    }

    private function createResponseEvent(Request $request, Response $response): ResponseEvent
    {
        $kernel = $this->prophesize(HttpKernelInterface::class);

        return new ResponseEvent(
            $kernel->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );
    }
}
