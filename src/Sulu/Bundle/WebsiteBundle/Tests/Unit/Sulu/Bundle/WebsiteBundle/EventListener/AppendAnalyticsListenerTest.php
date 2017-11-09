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
use Sulu\Bundle\WebsiteBundle\Entity\Analytics;
use Sulu\Bundle\WebsiteBundle\Entity\AnalyticsRepository;
use Sulu\Bundle\WebsiteBundle\EventListener\AppendAnalyticsListener;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\ParameterBag;
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
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/plain']);
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
        $analytics = $this->prophesize(Analytics::class);

        $analytics->getType()->willReturn('google');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')->willReturn([$analytics]);
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
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/html']);
        $event->getResponse()->willReturn($response->reveal());

        $engine->exists('SuluWebsiteBundle:Analytics:google/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:google/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:google/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $engine->exists('SuluWebsiteBundle:Analytics:google/body-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/body-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:google/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body><h1>Title</h1></body></html>'
        )->shouldBeCalled();
        $listener->onResponse($event->reveal());
    }

    public function testAppendWildcard()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);
        $analytics = $this->prophesize(Analytics::class);
        $analytics->getType()->willReturn('google');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');

        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('*.sulu.lo/*');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_WILDCARD);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('1.sulu.lo/2');

        $analyticsRepository->findByUrl('1.sulu.lo/2', 'sulu_io', 'prod')->willReturn([$analytics]);
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
        $request->getHost()->willReturn('1.sulu.lo');
        $request->getRequestUri()->willReturn('/2');
        $response = $this->prophesize(Response::class);
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/html']);
        $event->getResponse()->willReturn($response->reveal());

        $engine->exists('SuluWebsiteBundle:Analytics:google/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:google/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:google/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $engine->exists('SuluWebsiteBundle:Analytics:google/body-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/body-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:google/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()->willReturn('<html><head><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body><h1>Title</h1></body></html>'
        )->shouldBeCalled();

        $listener->onResponse($event->reveal());
    }

    public function testAppendGoogleTagManager()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);
        $analytics = $this->prophesize(Analytics::class);

        $analytics->getType()->willReturn('google_tag_manager');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')->willReturn([$analytics]);
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
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/html']);
        $event->getResponse()->willReturn($response->reveal());

        $engine->exists('SuluWebsiteBundle:Analytics:google_tag_manager/head-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:google_tag_manager/head-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $engine->exists('SuluWebsiteBundle:Analytics:google_tag_manager/head-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google_tag_manager/head-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:google_tag_manager/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:google_tag_manager/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<noscript><div>Blabla</div></noscript>');

        $engine->exists('SuluWebsiteBundle:Analytics:google_tag_manager/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:google_tag_manager/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()
            ->willReturn('<html><head><title>Test</title></head><body class="test"><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><script>var i = 0;</script><title>Test</title></head><body class="test"><noscript><div>Blabla</div></noscript><h1>Title</h1></body></html>'
        )->shouldBeCalled();
        $listener->onResponse($event->reveal());
    }

    public function testAppendPiwik()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);
        $analytics = $this->prophesize(Analytics::class);

        $analytics->getType()->willReturn('piwik');
        $analytics->getContent()->willReturn('<script>var i = 0;</script>');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')->willReturn([$analytics]);
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
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/html']);
        $event->getResponse()->willReturn($response->reveal());

        $engine->exists('SuluWebsiteBundle:Analytics:piwik/head-open.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:piwik/head-open.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $engine->exists('SuluWebsiteBundle:Analytics:piwik/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:piwik/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var i = 0;</script>');

        $engine->exists('SuluWebsiteBundle:Analytics:piwik/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:piwik/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<noscript><div>Blabla</div></noscript>');

        $engine->exists('SuluWebsiteBundle:Analytics:piwik/body-close.html.twig')->shouldBeCalled()->willReturn(false);
        $engine->render('SuluWebsiteBundle:Analytics:piwik/body-close.html.twig', ['analytics' => $analytics])
            ->shouldNotBeCalled();

        $response->getContent()
            ->willReturn('<html><head><title>Test</title></head><body class="test"><h1>Title</h1></body></html>');
        $response->setContent(
            '<html><head><title>Test</title><script>var i = 0;</script></head><body class="test"><noscript><div>Blabla</div></noscript><h1>Title</h1></body></html>'
        )->shouldBeCalled();
        $listener->onResponse($event->reveal());
    }

    public function testAppendCustom()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);
        $analytics = $this->prophesize(Analytics::class);

        $analytics->getType()->willReturn('custom');
        $analytics->getContent()->willReturn('{"position":"headOpen","value":"<script>XYZ</script>"}');
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrlExpression()->willReturn('sulu.lo/{localization}');
        $portalInformation->getType()->willReturn(RequestAnalyzerInterface::MATCH_TYPE_FULL);
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $requestAnalyzer->getPortalInformation()->willReturn($portalInformation->reveal());
        $requestAnalyzer->getAttribute('urlExpression')->willReturn('sulu.lo/{localization}');

        $analyticsRepository->findByUrl('sulu.lo/{localization}', 'sulu_io', 'prod')->willReturn([$analytics]);
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
        $response->reveal()->headers = new ParameterBag(['Content-Type' => 'text/html']);
        $event->getResponse()->willReturn($response->reveal());

        $engine->exists('SuluWebsiteBundle:Analytics:custom/head-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:custom/head-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('<script>var nice_var = false;</script>');

        $engine->exists('SuluWebsiteBundle:Analytics:custom/head-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:custom/head-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $engine->exists('SuluWebsiteBundle:Analytics:custom/body-open.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:custom/body-open.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $engine->exists('SuluWebsiteBundle:Analytics:custom/body-close.html.twig')->shouldBeCalled()->willReturn(true);
        $engine->render('SuluWebsiteBundle:Analytics:custom/body-close.html.twig', ['analytics' => $analytics])
            ->shouldBeCalled()->willReturn('');

        $response->getContent()
            ->willReturn('<html><head maybe-a-attribute-here="true"><title>Test</title></head><body><header><h1>Title</h1></header></body></html>');
        $response->setContent(
            '<html><head maybe-a-attribute-here="true"><script>var nice_var = false;</script><title>Test</title></head><body><header><h1>Title</h1></header></body></html>'
        )->shouldBeCalled();
        $listener->onResponse($event->reveal());
    }

    public function testAppendPreview()
    {
        $engine = $this->prophesize(EngineInterface::class);
        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $analyticsRepository = $this->prophesize(AnalyticsRepository::class);

        $analyticsRepository->findByUrl('1.sulu.lo/2', 'sulu_io', 'prod')->willReturn(['test' => 1]);
        $listener = new AppendAnalyticsListener(
            $engine->reveal(),
            $requestAnalyzer->reveal(),
            $analyticsRepository->reveal(),
            'prod',
            true
        );

        $event = $this->prophesize(FilterResponseEvent::class);
        $event->getRequest()->shouldNotBeCalled();
        $event->getResponse()->shouldNotBeCalled();

        $listener->onResponse($event->reveal());
    }
}
