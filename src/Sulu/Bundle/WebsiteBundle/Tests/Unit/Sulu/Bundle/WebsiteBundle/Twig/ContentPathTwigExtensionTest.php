<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Twig;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathTwigExtension;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ContentPathTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $suluWebspace;

    /**
     * @var Webspace
     */
    private $testWebspace;

    /**
     * @var string
     */
    private $environment = 'prod';

    /**
     * @var ContentPathTwigExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->suluWebspace = $this->prophesize(Webspace::class);
        $this->suluWebspace->getKey()->willReturn('sulu_io');
        $this->suluWebspace->hasDomain('www.sulu.io', $this->environment, 'de')->willReturn(true);
        $this->suluWebspace->hasDomain('www.test.io', $this->environment, 'de')->willReturn(false);

        $this->testWebspace = $this->prophesize(Webspace::class);
        $this->testWebspace->getKey()->willReturn('test_io');
        $this->testWebspace->hasDomain('www.test.io', $this->environment, 'de')->willReturn(true);
        $this->testWebspace->hasDomain('www.sulu.io', $this->environment, 'de')->willReturn(false);

        $this->requestAnalyzer->getAttribute('scheme')->willReturn('http');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));
        $this->requestAnalyzer->getWebspace()->willReturn($this->suluWebspace->reveal());

        $this->webspaceManager->findWebspaceByKey('sulu_io')->willReturn($this->suluWebspace->reveal());
        $this->webspaceManager->findWebspaceByKey('test_io')->willReturn($this->testWebspace->reveal());

        $this->extension = new ContentPathTwigExtension(
            $this->webspaceManager->reveal(),
            $this->environment,
            $this->requestAnalyzer->reveal()
        );
    }

    public function testGetContentPath()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.sulu.io');
        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'de',
            'sulu_io',
            'www.sulu.io',
            'http'
        )->willReturn('www.sulu.io/de/test')->shouldBeCalledTimes(1);

        $this->assertEquals('www.sulu.io/de/test', $this->extension->getContentPath('/test'));
    }

    public function testGetContentPathWithLocaleForDifferentDomain()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('en.sulu.io');
        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'de',
            'sulu_io',
            null,
            'http'
        )->willReturn('de.sulu.io/test');
        $this->suluWebspace->hasDomain('en.sulu.io', 'prod', 'de')->willReturn(false);
        $this->assertEquals('de.sulu.io/test', $this->extension->getContentPath('/test', null, 'de'));
    }

    public function testGetContentPathWithWebspaceKey()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.test.io');
        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'de',
            'test_io',
            'www.test.io',
            'http'
        )->willReturn('www.sulu.io/de/test')->shouldBeCalledTimes(1);

        $this->assertEquals('www.sulu.io/de/test', $this->extension->getContentPath('/test', 'test_io'));
    }

    public function testGetContentPathWithWebspaceKeyNotFoundForDomain()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.test.io');

        // empty webspace object will not contain domain as tested in isFromDomain call
        $webspace = new Webspace();
        $this->webspaceManager->findWebspaceByKey('test_io')->willReturn($webspace);

        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'de',
            'test_io',
            null,
            'http'
        )->willReturn('www.test.io/de/test')->shouldBeCalledTimes(1);

        $this->assertEquals('www.test.io/de/test', $this->extension->getContentPath('/test', 'test_io'));
    }

    public function testGetContentPathWithWebspaceKeyHostNotWebspace()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.xy.io');
        $this->testWebspace->hasDomain('www.xy.io', $this->environment, 'de')->willReturn(false);
        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'de',
            'test_io',
            null,
            'http'
        )->willReturn('www.test.io/de/test')->shouldBeCalledTimes(1);

        $this->assertEquals('www.test.io/de/test', $this->extension->getContentPath('/test', 'test_io'));
    }

    public function testGetContentPathWithWebspaceKeyAndDomain()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.sulu.io');
        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            $this->environment,
            'en',
            'test_io',
            'www.test.io',
            'http'
        )->willReturn(null)->shouldBeCalledTimes(1);

        $this->assertEquals(
            '/test',
            $this->extension->getContentPath('/test', 'test_io', 'en', 'www.test.io')
        );
    }

    public function testGetContentPathExternalUrl()
    {
        $this->requestAnalyzer->getAttribute('host')->willReturn('www.sulu.io');
        $this->webspaceManager->findUrlByResourceLocator(
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->shouldNotBeCalled();

        $this->assertEquals(
            'http://www.google.at',
            $this->extension->getContentPath('http://www.google.at')
        );
        $this->assertEquals(
            'http://www.google.at',
            $this->extension->getContentPath('http://www.google.at', 'test_io')
        );
        $this->assertEquals(
            'http://www.google.at',
            $this->extension->getContentPath('http://www.google.at', 'test_io', 'en')
        );
        $this->assertEquals(
            'http://www.google.at',
            $this->extension->getContentPath('http://www.google.at', 'test_io', 'en', 'www.test.io')
        );
    }
}
