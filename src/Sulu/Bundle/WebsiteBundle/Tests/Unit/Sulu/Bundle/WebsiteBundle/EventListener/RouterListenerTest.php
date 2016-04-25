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
use Symfony\Component\HttpKernel\EventListener\RouterListener as BaseRouteListener;

class RouterListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BaseRouteListener
     */
    private $baseRouteListener;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouterListener
     */
    private $routerListener;

    /**
     * @var GetResponseEvent
     */
    private $event;

    public function setUp()
    {
        $this->baseRouteListener = $this->prophesize(BaseRouteListener::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->routerListener = new RouterListener($this->baseRouteListener->reveal(), $this->requestAnalyzer->reveal());

        $this->event = $this->prophesize(GetResponseEvent::class);
    }

    public function testAnalyzeRequest()
    {
        $request = new Request([], [], ['_requestAnalyzer' => true]);
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldBeCalled();

        $this->routerListener->onKernelRequest($this->event->reveal());
    }

    public function testAnalyzeRequestDisabled()
    {
        $request = new Request([], [], ['_requestAnalyzer' => false]);
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldNotBeCalled();

        $this->routerListener->onKernelRequest($this->event->reveal());
    }

    public function testAnalyzeRequestDefault()
    {
        $request = new Request();
        $this->event->getRequest()->willReturn($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldBeCalled();

        $this->routerListener->onKernelRequest($this->event->reveal());
    }
}
