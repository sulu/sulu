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

        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'sulu.lo', 'REQUEST_URI' => '/test']);

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

        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'sulu.lo', 'REQUEST_URI' => '/test']);

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

        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'sulu.lo', 'REQUEST_URI' => '/de']);

        $this->replacer->replaceHost(null, 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('sulu.lo', 'sulu.lo')->willReturn('sulu.lo');
        $this->replacer->replaceHost('sulu.lo/de', 'sulu.lo')->willReturn('sulu.lo/de');

        $attributes = $this->provider->process($request, new RequestAttributes());

        $this->assertEquals($portalInformation1, $attributes->getAttribute('portalInformation'));
    }

    public function provideValidateData()
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
     * @dataProvider provideValidateData
     */
    public function testValidate($attributes, $exception = null, $message = '')
    {
        if ($exception !== null) {
            $this->setExpectedException($exception, $message);
        }

        $this->assertTrue($this->provider->validate(new RequestAttributes($attributes)));
    }
}
