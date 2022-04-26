<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\WebsiteBundle\EventListener\RouterListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener as BaseRouteListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RouterListenerTest extends TestCase
{
    use ProphecyTrait;

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
     * @var HttpKernelInterface
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->baseRouteListener = $this->prophesize(BaseRouteListener::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->routerListener = new RouterListener($this->baseRouteListener->reveal(), $this->requestAnalyzer->reveal());
    }

    public function testAnalyzeRequest()
    {
        $request = new Request([], [], ['_requestAnalyzer' => true]);
        $event = $this->createRequestEvent($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldBeCalled();

        $this->routerListener->onKernelRequest($event);
    }

    public function testAnalyzeRequestDisabled()
    {
        $request = new Request([], [], ['_requestAnalyzer' => false]);
        $event = $this->createRequestEvent($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldNotBeCalled();

        $this->routerListener->onKernelRequest($event);
    }

    public function testAnalyzeRequestDisabledByEsiInProdEnv()
    {
        $request = new Request([], [], ['_requestAnalyzer' => '0']);
        $event = $this->createRequestEvent($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldNotBeCalled();

        $this->routerListener->onKernelRequest($event);
    }

    public function testAnalyzeRequestDefault()
    {
        $request = new Request();
        $event = $this->createRequestEvent($request);

        $this->requestAnalyzer->analyze($request)->shouldBeCalled();
        $this->requestAnalyzer->validate($request)->shouldBeCalled();

        $this->routerListener->onKernelRequest($event);
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->kernel->reveal(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }
}
