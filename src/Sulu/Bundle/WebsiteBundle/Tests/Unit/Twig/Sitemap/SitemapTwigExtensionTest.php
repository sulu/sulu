<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Twig\Sitemap;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrlCollectorInterface;
use Sulu\Bundle\WebsiteBundle\Twig\Sitemap\SitemapTwigExtension;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SitemapTwigExtensionTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SitemapUrlCollectorInterface>
     */
    private ObjectProphecy $sitemapUrlCollector;

    /**
     * @var ObjectProphecy<SitemapProviderPoolInterface>
     */
    private ObjectProphecy $sitemapProviderPool;

    /**
     * @var ObjectProphecy<WebspaceManagerInterface>
     */
    private ObjectProphecy $webspaceManager;

    /**
     * @var ObjectProphecy<RequestAnalyzerInterface>
     */
    private ObjectProphecy $requestAnalyzer;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->sitemapUrlCollector = $this->prophesize(SitemapUrlCollectorInterface::class);
        $this->sitemapProviderPool = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);
        $this->requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);

        $this->requestStack = new RequestStack();
    }

    public function testSitemapUsesCurrentRequestAndLocalization(): void
    {
        $this->requestStack->push(Request::create('http://sulu.lo/sitemap'));

        $localization = new Localization('de');
        $this->requestAnalyzer->getCurrentLocalization()->willReturn($localization);

        $sitemapUrl = new SitemapUrl('http://sulu.lo/de', 'de', 'de', null, null, null, [], 'Homepage');
        $this->sitemapUrlCollector->collect('http', 'sulu.lo', 'de', null, 1)
            ->willReturn([$sitemapUrl])
            ->shouldBeCalledOnce();

        $extension = $this->createExtension();

        $this->assertSame([$sitemapUrl], $extension->sitemapFunction());
        // the second call is served by the memoize cache
        $this->assertSame([$sitemapUrl], $extension->sitemapFunction());
    }

    public function testSitemapWithExplicitArguments(): void
    {
        $this->requestStack->push(Request::create('https://sulu.lo/sitemap'));

        $this->requestAnalyzer->getCurrentLocalization()->shouldNotBeCalled();

        $sitemapUrl = new SitemapUrl('https://sulu.lo/en/article', 'en', 'de');
        $this->sitemapUrlCollector->collect('https', 'sulu.lo', 'en', 'articles', 2)
            ->willReturn([$sitemapUrl])
            ->shouldBeCalledOnce();

        $extension = $this->createExtension();

        $this->assertSame([$sitemapUrl], $extension->sitemapFunction('en', 'articles', 2));
    }

    public function testSitemapWithoutRequest(): void
    {
        $this->sitemapUrlCollector->collect(Argument::cetera())->shouldNotBeCalled();

        $extension = $this->createExtension();

        $this->assertSame([], $extension->sitemapFunction());
    }

    public function testSitemapUrl(): void
    {
        $webspace = new Webspace();
        $webspace->setKey('sulu_io');

        $this->requestAnalyzer->getWebspace()->willReturn($webspace);
        $this->requestAnalyzer->getCurrentLocalization()->willReturn(new Localization('de'));

        $this->webspaceManager->findUrlByResourceLocator('/products', 'prod', 'de', 'sulu_io')
            ->willReturn('http://sulu.lo/products');

        $extension = $this->createExtension();

        $this->assertSame('http://sulu.lo/products', $extension->sitemapUrlFunction('/products'));
    }

    public function testSitemapUrlWithExplicitArguments(): void
    {
        $this->requestAnalyzer->getWebspace()->shouldNotBeCalled();
        $this->requestAnalyzer->getCurrentLocalization()->shouldNotBeCalled();

        $this->webspaceManager->findUrlByResourceLocator('/products', 'prod', 'en', 'other_io')
            ->willReturn('http://other.lo/en/products');

        $extension = $this->createExtension();

        $this->assertSame(
            'http://other.lo/en/products',
            $extension->sitemapUrlFunction('/products', 'en', 'other_io')
        );
    }

    public function testSitemapUrlWithoutRequestAnalyzer(): void
    {
        $this->webspaceManager->findUrlByResourceLocator(Argument::cetera())->shouldNotBeCalled();

        $extension = $this->createExtension(false);

        $this->assertNull($extension->sitemapUrlFunction('/products'));
    }

    public function testSitemapAliases(): void
    {
        $this->sitemapProviderPool->getProviders()->willReturn([
            'pages' => $this->prophesize(SitemapProviderInterface::class)->reveal(),
            'articles' => $this->prophesize(SitemapProviderInterface::class)->reveal(),
        ]);

        $extension = $this->createExtension();

        $this->assertSame(['pages', 'articles'], $extension->sitemapAliasesFunction());
    }

    private function createExtension(bool $withRequestAnalyzer = true): SitemapTwigExtension
    {
        return new SitemapTwigExtension(
            $this->sitemapUrlCollector->reveal(),
            $this->sitemapProviderPool->reveal(),
            $this->webspaceManager->reveal(),
            $this->requestStack,
            new Memoize(new ArrayAdapter(), 3600),
            'prod',
            3600,
            $withRequestAnalyzer ? $this->requestAnalyzer->reveal() : null
        );
    }
}
