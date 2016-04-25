<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use Sulu\Bundle\WebsiteBundle\EventListener\RouterListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouterListener
     */
    private $requestListener;

    /**
     * @var GetResponseEvent
     */
    private $event;

    public function setUp()
    {
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->requestListener = new RouterListener($this->requestAnalyzer->reveal());

        $this->event = $this->prophesize(GetResponseEvent::class);
    }

    public function testAnalyzeRequest()
    {
        $request = new Request([], [], ['_requestAnalyzer' => true]);
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();

        $this->requestListener->analyzeRequest($this->event->reveal());
    }

    public function testAnalyzeRequestDisabled()
    {
        $request = new Request([], [], ['_requestAnalyzer' => false]);
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldNotBeCalled();

        $this->requestListener->analyzeRequest($this->event->reveal());
    }

    public function testAnalyzeRequestDefault()
    {
        $request = new Request();
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();

        $this->requestListener->analyzeRequest($this->event->reveal());
    }
}
