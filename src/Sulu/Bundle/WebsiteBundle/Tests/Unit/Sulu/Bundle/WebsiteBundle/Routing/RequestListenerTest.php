<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Routing;

use Sulu\Bundle\WebsiteBundle\Routing\RequestListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class RequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var PortalInformation
     */
    private $portalInformation;

    /**
     * @var RequestContext
     */
    private $requestContext;

    public function setUp()
    {
        parent::setUp();

        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->portalInformation = $this->prophesize(PortalInformation::class);
        $this->requestContext = $this->prophesize(RequestContext::class);
        $this->event = $this->prophesize(GetResponseEvent::class);
    }

    public function testRequestAnalyzer()
    {
        $this->portalInformation->getPrefix()->willReturn('test/');
        $this->portalInformation->getHost()->willReturn('sulu.io');
        $this->requestAnalyzer->getPortalInformation()->willReturn($this->portalInformation);

        $this->requestContext->hasParameter('prefix')->willReturn(false);
        $this->requestContext->hasParameter('host')->willReturn(false);

        $this->requestContext->setParameter('prefix', 'test/')->shouldBeCalled();
        $this->requestContext->setParameter('host', 'sulu.io')->shouldBeCalled();

        $this->router->getContext()->willReturn($this->requestContext);

        $requestListener = new RequestListener($this->router->reveal(), $this->requestAnalyzer->reveal());
        $requestListener->onRequest($this->event->reveal());
    }
}
