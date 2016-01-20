<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\EventListener;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Sulu\Bundle\WebsiteBundle\EventListener\AppendAnalyticsListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Templating\EngineInterface;

class AppendAnalyticsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function formatProvider()
    {
        return [['json'], ['xml']];
    }

    /**
     * @dataProvider formatProvider
     *
     * @param string $format
     */
    public function testAppendFormatNoEffect($format)
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $request->getRequestFormat()->willReturn($format);
        $response = $this->prophesize(Response::class);
        $event->getResponse()->willReturn($response->reveal());

        $listener->onResponse($event->reveal());

        $engine->render(Argument::any(), Argument::any())->shouldNotBeCalled();
        $response->setContent(Argument::any())->shouldNotBeCalled();
    }

    public function testAppendFormat()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);

        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'prod')->willReturn(['test' => 1]);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod'
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $request = $this->prophesize(Request::class);
        $event->getRequest()->willReturn($request->reveal());
        $request->getRequestFormat()->willReturn('html');
        $response = $this->prophesize(Response::class);
        $event->getResponse()->willReturn($response->reveal());

        $engine->render('SuluWebsiteBundle:Analytics:website.html.twig', ['analytics' => ['test' => 1]])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');
        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title></head><body><h1>Title</h1><script>var i = 0;</script></body></html>'
        )->shouldBeCalled();
        $listener->onResponse($event->reveal());
    }
}
