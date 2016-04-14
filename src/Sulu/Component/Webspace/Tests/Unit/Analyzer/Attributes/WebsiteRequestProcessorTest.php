<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use Prophecy\Argument;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Url\ReplacerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class WebsiteRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteRequestProcessor
     */
    private $provider;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var ReplacerInterface
     */
    private $replacer;

    public function setUp()
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);
        $this->replacer = $this->prophesize(ReplacerInterface::class);

        $this->provider = new WebsiteRequestProcessor(
            $this->webspaceManager->reveal(),
            $this->contentMapper->reveal(),
            $this->replacer->reveal(),
            'prod'
        );
    }

    public function provideAnalyze()
    {
        return [
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                ],
            ],
        ];
    }

    public function provideAnalyzeWithFormat()
    {
        return [
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to.html',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'html',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo',
                    'path_info' => '/test/path/to.rss',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_PARTIAL,
                    'redirect' => 'sulu.lo/test',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                    'format' => 'rss',
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'format' => null,
                ],
            ],
            [
                [
                    'portal_url' => 'sulu.lo/test',
                    'path_info' => '/test/path/to/test.min.css',
                    'match_type' => RequestAnalyzerInterface::MATCH_TYPE_FULL,
                    'redirect' => '',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to/test',
                    'portal_url' => 'sulu.lo/test',
                    'format' => 'css',
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideAnalyze
     */
    public function testProcess($config, $expected = [])
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            null,
            $config['redirect']
        );

        $this->prepareWebspaceManager($portalInformation);

        $request = $this->getMock(Request::class);
        $request->request = new ParameterBag(['post' => 1]);
        $request->query = new ParameterBag(['get' => 1]);
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue($config['path_info']));
        $request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals('de_at', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());
        $this->assertNull($attributes->getAttribute('segment'));

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals(['post' => 1], $attributes->getAttribute('postParameter'));
        $this->assertEquals(['get' => 1], $attributes->getAttribute('getParameter'));
    }

    /**
     * @dataProvider provideAnalyzeWithFormat
     */
    public function testProcessWithFormat($config, $expected = [])
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation = new PortalInformation(
            $config['match_type'],
            $webspace,
            $portal,
            $localization,
            $config['portal_url'],
            null,
            $config['redirect']
        );

        $this->prepareWebspaceManager($portalInformation);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(['post' => 1]);
        $request->query = new ParameterBag(['get' => 1]);
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue($config['path_info']));
        $request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals('de_at', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());
        $this->assertNull($attributes->getAttribute('segment'));

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals($expected['format'], $attributes->getAttribute('format'));
        $this->assertEquals(['post' => 1], $attributes->getAttribute('postParameter'));
        $this->assertEquals(['get' => 1], $attributes->getAttribute('getParameter'));
    }

    public function testProcessHostReplacer()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            '{host}',
            null,
            '{host}/de'
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            '{host}/de'
        );

        $portalInformation3 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.io/de'
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2, $portalInformation3]);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(['post' => 1]);
        $request->query = new ParameterBag(['get' => 1]);
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/test'));
        $request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $this->replacer->replaceHost(null, 'sulu.lo')->willReturn(null);
        $this->replacer->replaceHost('{host}', 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('{host}/de', 'sulu.lo')->willReturn('sulu.lo/de');
        $this->replacer->replaceHost('sulu.io/de', 'sulu.lo')->willReturn('sulu.io/de');

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals('sulu.lo', $attributes->getAttribute('portalInformation')->getUrl());
        $this->assertEquals('sulu.lo', $portalInformation1->getUrl());
        $this->assertEquals('sulu.lo/de', $portalInformation2->getUrl());
        $this->assertEquals('sulu.io/de', $portalInformation3->getUrl());
    }

    public function testProcessHostPriority()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            '{host}',
            null,
            '{host}',
            null,
            false,
            '{host}',
            -5
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            null,
            null,
            false,
            'sulu.lo',
            5
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1, $portalInformation2]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2]);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(['post' => 1]);
        $request->query = new ParameterBag(['get' => 1]);
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/test'));
        $request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $this->replacer->replaceHost(null, 'sulu.lo')->willReturn(null);
        $this->replacer->replaceHost('{host}', 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('sulu.lo', 'sulu.lo')->willReturn('sulu.lo');

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals($portalInformation2, $attributes->getAttribute('portalInformation'));
        $this->assertEquals('sulu.lo', $portalInformation1->getUrl());
        $this->assertEquals('sulu.lo', $portalInformation2->getUrl());
    }

    public function testProcessSamePriorityDifferentLength()
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu');

        $portal = new Portal();
        $portal->setKey('sulu');

        $localization = new Localization();
        $localization->setCountry('at');
        $localization->setLanguage('de');

        $portalInformation1 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo/de',
            null,
            null,
            null,
            false,
            'sulu.lo/de',
            5
        );

        $portalInformation2 = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            $webspace,
            $portal,
            $localization,
            'sulu.lo',
            null,
            null,
            null,
            false,
            'sulu.lo',
            5
        );

        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), 'prod')
            ->willReturn([$portalInformation1, $portalInformation2]);
        $this->webspaceManager->getPortalInformations('prod')
            ->willReturn([$portalInformation1, $portalInformation2]);

        $request = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $request->request = new ParameterBag(['post' => 1]);
        $request->query = new ParameterBag(['get' => 1]);
        $request->expects($this->any())->method('getHost')->will($this->returnValue('sulu.lo'));
        $request->expects($this->any())->method('getPathInfo')->will($this->returnValue('/de'));
        $request->expects($this->any())->method('getScheme')->will($this->returnValue('http'));

        $this->replacer->replaceHost(null, 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('sulu.lo', 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('sulu.lo/de', 'sulu.lo')->willReturn('sulu.lo/de');

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals($portalInformation1, $attributes->getAttribute('portalInformation'));
    }

    public function provideAnalyzeData()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);

        return [
            [['portalInformation' => $portalInformation]],
            [
                ['requestUri' => 'http://sulu.io'],
                UrlMatchNotFoundException::class,
                'There exists no portal for the URL "http://sulu.io"',
            ],
        ];
    }

    /**
     * @dataProvider provideAnalyzeData
     */
    public function testAnalyze($attributes, $exception = null, $message = '')
    {
        if ($exception !== null) {
            $this->setExpectedException($exception, $message);
        }

        $this->assertTrue($this->provider->validate(new RequestAttributes($attributes)));
    }

    /**
     * @param $portalInformation
     */
    protected function prepareWebspaceManager($portalInformation)
    {
        $this->webspaceManager->findPortalInformationsByUrl(Argument::any(), Argument::any())
            ->willReturn([$portalInformation]);
        $this->webspaceManager->getPortalInformations(Argument::any())->willReturn([]);
    }
}
