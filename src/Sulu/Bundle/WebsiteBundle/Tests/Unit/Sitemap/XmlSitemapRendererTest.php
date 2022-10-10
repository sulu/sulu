<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sitemap;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRenderer;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Twig\Environment;

class XmlSitemapRendererTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SitemapProviderPoolInterface>
     */
    protected $providerPoolInterface;

    /**
     * @var ObjectProphecy<Environment>
     */
    protected $engine;

    /**
     * @var XmlSitemapRenderer
     */
    protected $renderer;

    public function setUp(): void
    {
        $this->providerPoolInterface = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->engine = $this->prophesize(Environment::class);

        $this->renderer = new XmlSitemapRenderer($this->providerPoolInterface->reveal(), $this->engine->reveal(), '/');
    }

    public function testRenderIndexNoNeed(): void
    {
        $sitemaps = [new Sitemap('pages', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex('http', 'sulu.io')->willReturn($sitemaps);

        $this->assertEquals(null, $this->renderer->renderIndex('http', 'sulu.io'));
    }

    public function testRenderIndexNoNeedMultipleProviders(): void
    {
        $sitemaps = [
            new Sitemap('test', 0),
            new Sitemap('pages', 1),
        ];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $testProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn([
            'test' => $testProvider->reveal(),
            'pages' => $pagesProvider->reveal(),
        ]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->hasProvider('test')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getProvider('test')->willReturn($testProvider);
        $this->providerPoolInterface->getIndex('http', 'sulu.io')->willReturn($sitemaps);

        $this->assertEquals(null, $this->renderer->renderIndex('http', 'sulu.io'));
    }

    public function testRenderIndexMorePages(): void
    {
        $sitemaps = [new Sitemap('pages', 2)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex('http', 'sulu.io')->willReturn($sitemaps);

        $this->engine->render(
            '@SuluWebsite/Sitemap/sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => 'sulu.io', 'scheme' => 'http']
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex('http', 'sulu.io'));
    }

    public function testRenderIndexMoreProviders(): void
    {
        $sitemaps = [new Sitemap('pages', 1), new Sitemap('article', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $articleProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(
            ['pages' => $pagesProvider->reveal(), 'article' => $articleProvider->reveal()]
        );
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex('http', 'sulu.io')->willReturn($sitemaps);

        $this->engine->render(
            '@SuluWebsite/Sitemap/sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => 'sulu.io', 'scheme' => 'http']
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex('http', 'sulu.io'));
    }

    public function testRenderIndexWithSchemeAndDomain(): void
    {
        $sitemaps = [new Sitemap('pages', 1), new Sitemap('article', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $articleProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(
            ['pages' => $pagesProvider->reveal(), 'article' => $articleProvider->reveal()]
        );
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex('http', 'sulu.io')->willReturn($sitemaps);

        $this->engine->render(
            '@SuluWebsite/Sitemap/sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => 'sulu.io', 'scheme' => 'http']
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex('http', 'sulu.io'));
    }

    public function testRenderSitemap(): void
    {
        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);

        $entries = [new SitemapUrl('http://sulu.io', 'en', 'en')];
        $pagesProvider->build(1, 'http', 'sulu.io')->willReturn($entries);
        $pagesProvider->getMaxPage('http', 'sulu.io')->willReturn(1);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu');

        $portal = $this->prophesize(Portal::class);
        $portal->getKey()->willReturn('sulu_io');
        $portal->getWebspace()->willReturn($webspace->reveal());
        $portal->getXDefaultLocalization()->willReturn(new Localization('de'));

        $this->engine->render(
            '@SuluWebsite/Sitemap/sitemap.xml.twig',
            [
                'domain' => 'sulu.io',
                'scheme' => 'http',
                'entries' => $entries,
            ]
        )->willReturn('<html/>');

        $this->assertEquals(
            '<html/>',
            $this->renderer->renderSitemap('pages', 1, 'http', 'sulu.io')
        );
    }
}
