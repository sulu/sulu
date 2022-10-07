<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RequestListenerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private $requestAnalyzer;

    /**
     * @var ObjectProphecy<RouterInterface>
     */
    private $router;

    /**
     * @var ObjectProphecy<PortalInformation>
     */
    private $portalInformation;

    /**
     * @var ObjectProphecy<RequestContext>
     */
    private $requestContext;

    /**
     * @var ObjectProphecy<HttpKernelInterface>
     */
    private $kernel;

    public function setUp(): void
    {
        $this->kernel = $this->prophesize(HttpKernelInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->portalInformation = $this->prophesize(PortalInformation::class);
        $this->requestContext = $this->prophesize(RequestContext::class);
    }

    public function testRequestAnalyzer(): void
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        $this->requestContext->hasParameter('prefix')->willReturn(false);
        $this->requestContext->hasParameter('host')->willReturn(false);

        $this->requestContext->setParameter('prefix', 'test/')->shouldBeCalled()
            ->willReturn($this->requestContext->reveal());
        $this->requestContext->setParameter('host', 'sulu.io')->shouldBeCalled()
            ->willReturn($this->requestContext->reveal());

        $this->router->getContext()->willReturn($this->requestContext);

        $event = $this->createRequestEvent(new Request());

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($event);
    }

    private function createRequestEvent(Request $request): RequestEvent
    {
        return new RequestEvent(
            $this->kernel->reveal(),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );
    }
}
