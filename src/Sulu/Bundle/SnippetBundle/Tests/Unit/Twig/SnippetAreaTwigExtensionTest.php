<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Unit\Twig;

use Sulu\Bundle\SnippetBundle\Snippet\DefaultSnippetManagerInterface;
use Sulu\Bundle\SnippetBundle\Snippet\SnippetResolverInterface;
use Sulu\Bundle\SnippetBundle\Twig\SnippetAreaTwigExtension;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;

class SnippetAreaTwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultSnippetManagerInterface
     */
    private $defaultSnippetManager;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var Webspace
     */
    private $webspace;

    /**
     * @var Localization
     */
    private $localization;

    /**
     * @var SnippetResolverInterface
     */
    private $snippetResolver;

    public function testLoadByArea()
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->defaultSnippetManager->loadIdentifier('sulu_io', 'test')->shouldBeCalled()->willReturn('1234');
        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'de')->shouldBeCalled()->willReturn([['title' => 'Test Snippet']]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal()
        );

        $this->assertEquals(
            ['title' => 'Test Snippet'],
            $twigExtension->loadByArea('test')
        );
    }

    public function testLoadByAreaNotExist()
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->defaultSnippetManager->loadIdentifier('sulu_io', 'test')->shouldBeCalled()->willReturn();
        $this->snippetResolver->resolve([], 'sulu_io', 'de')->shouldBeCalled()->willReturn([]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal()
        );

        $this->assertEquals(
            null,
            $twigExtension->loadByArea('test')
        );
    }

    public function testLoadByAreaDeletedSnippet()
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->defaultSnippetManager->loadIdentifier('sulu_io', 'test')->shouldBeCalled()->willReturn('1234');
        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'de')->shouldBeCalled()->willReturn([]);

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal()
        );

        $this->assertEquals(
            null,
            $twigExtension->loadByArea('test')
        );
    }

    public function testLoadByAreaOtherLocale()
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->snippetResolver->resolve(['1234'], 'sulu_io', 'en')->shouldBeCalled()->willReturn([['title' => 'Test Snippet EN']]);
        $this->defaultSnippetManager->loadIdentifier('sulu_io', 'test')->shouldBeCalled()->willReturn('1234');

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal()
        );

        $this->assertEquals(
            ['title' => 'Test Snippet EN'],
            $twigExtension->loadByArea('test', null, 'en')
        );
    }

    public function testLoadByAreaOtherWebspace()
    {
        $this->defaultSnippetManager = $this->prophesize(DefaultSnippetManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $this->webspace = $this->prophesize(Webspace::class);
        $this->webspace->getKey()->willReturn('sulu_io');
        $this->localization = $this->prophesize(Localization::class);
        $this->localization->getLocale()->willReturn('de');
        $this->requestAnalyzer->getWebspace()->willReturn($this->webspace->reveal());
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($this->localization);
        $this->snippetResolver = $this->prophesize(SnippetResolverInterface::class);
        $this->snippetResolver->resolve(['1234'], 'demo_io', 'en')->shouldBeCalled()->willReturn([['title' => 'Test Snippet EN']]);
        $this->defaultSnippetManager->loadIdentifier('demo_io', 'test')->shouldBeCalled()->willReturn('1234');

        $twigExtension = new SnippetAreaTwigExtension(
            $this->defaultSnippetManager->reveal(),
            $this->requestAnalyzer->reveal(),
            $this->snippetResolver->reveal()
        );

        $this->assertEquals(
            ['title' => 'Test Snippet EN'],
            $twigExtension->loadByArea('test', 'demo_io', 'en')
        );
    }
}
