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

use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Exception\UrlMatchNotFoundException;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
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

    public function setUp()
    {
        $this->webspaceManager = $this->getMockForAbstractClass(
            WebspaceManagerInterface::class,
            [],
            '',
            true,
            true,
            true,
            ['findPortalInformationByUrl']
        );

        $this->contentMapper = $this->prophesize(ContentMapperInterface::class);

        $this->provider = new WebsiteRequestProcessor($this->webspaceManager, $this->contentMapper->reveal(), 'prod');
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
        $this->webspaceManager->expects($this->any())->method('findPortalInformationsByUrl')->will(
            $this->returnValue([$portalInformation])
        );
    }
}
