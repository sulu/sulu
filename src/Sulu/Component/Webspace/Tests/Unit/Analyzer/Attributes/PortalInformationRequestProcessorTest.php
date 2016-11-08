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

use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\Attributes\PortalInformationRequestProcessor;
use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\HttpFoundation\Request;

class PortalInformationRequestProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PortalInformationRequestProcessor
     */
    private $portalInformationRequestProcessor;

    public function setUp()
    {
        $this->portalInformationRequestProcessor = new PortalInformationRequestProcessor();
    }

    /**
     * @dataProvider provideProcess
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
            $config['redirect'],
            null,
            false,
            $config['url_expression']
        );

        $request = new Request(
            ['get' => 1],
            ['post' => 1],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo', 'REQUEST_URI' => $config['path_info']]
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertEquals($localization->getLocale(), $request->getLocale());

        $this->assertEquals('de_at', $attributes->getAttribute('localization'));
        $this->assertEquals('sulu', $attributes->getAttribute('webspace')->getKey());
        $this->assertEquals('sulu', $attributes->getAttribute('portal')->getKey());
        $this->assertNull($attributes->getAttribute('segment'));

        $this->assertEquals($expected['portal_url'], $attributes->getAttribute('portalUrl'));
        $this->assertEquals($expected['redirect'], $attributes->getAttribute('redirect'));
        $this->assertEquals($expected['resource_locator'], $attributes->getAttribute('resourceLocator'));
        $this->assertEquals($expected['resource_locator_prefix'], $attributes->getAttribute('resourceLocatorPrefix'));
        $this->assertEquals($expected['url_expression'], $attributes->getAttribute('urlExpression'));
        $this->assertEquals(['post' => 1], $attributes->getAttribute('postParameter'));
        $this->assertEquals(['get' => 1], $attributes->getAttribute('getParameter'));
    }

    /**
     * @dataProvider provideProcessWithFormat
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

        $request = new Request(
            ['get' => 1],
            ['post' => 1],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo', 'REQUEST_URI' => $config['path_info']]
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertEquals($localization->getLocale(), $request->getLocale());
        if ($expected['format']) {
            $this->assertEquals($expected['format'], $request->getRequestFormat());
        }

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

    public function testProcessWithPort()
    {
        $portalInformation = new PortalInformation(
            RequestAnalyzerInterface::MATCH_TYPE_FULL,
            null,
            null,
            null,
            'sulu.lo:8000/test'
        );

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_HOST' => 'sulu.lo:8000', 'REQUEST_URI' => '/test/path/to']
        );

        $attributes = $this->portalInformationRequestProcessor->process(
            $request,
            new RequestAttributes(['portalInformation' => $portalInformation])
        );

        $this->assertEquals('/path/to', $attributes->getAttribute('resourceLocator'));
        $this->assertEquals('/test', $attributes->getAttribute('resourceLocatorPrefix'));
    }

    public function testValidate()
    {
        $this->assertTrue($this->portalInformationRequestProcessor->validate(new RequestAttributes()));
    }

    public function provideProcess()
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
                    'url_expression' => 'sulu.lo/{localization}',
                ],
                [
                    'redirect' => null,
                    'resource_locator_prefix' => '/test',
                    'resource_locator' => '/path/to',
                    'portal_url' => 'sulu.lo/test',
                    'url_expression' => 'sulu.lo/{localization}',
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
                    'url_expression' => 'sulu.lo/{localization}',
                ],
                [
                    'redirect' => 'sulu.lo/test',
                    'resource_locator_prefix' => '',
                    'resource_locator' => '/test/path/to',
                    'portal_url' => 'sulu.lo',
                    'url_expression' => 'sulu.lo/{localization}',
                ],
            ],
        ];
    }

    public function provideProcessWithFormat()
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
}
