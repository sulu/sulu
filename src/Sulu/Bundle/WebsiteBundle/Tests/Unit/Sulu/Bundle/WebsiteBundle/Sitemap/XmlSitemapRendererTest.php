<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Sitemap;

use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRenderer;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\Webspace;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class XmlSitemapRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SitemapProviderPoolInterface
     */
    protected $providerPoolInterface;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var XmlSitemapRenderer
     */
    protected $renderer;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->providerPoolInterface = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->engine = $this->prophesize(EngineInterface::class);

        $this->renderer = new XmlSitemapRenderer($this->providerPoolInterface->reveal(), $this->engine->reveal(), '/');
    }

    public function testRenderIndexNoNeed()
    {
        $sitemaps = [new Sitemap('pages', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex()->willReturn($sitemaps);

        $this->engine->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps]
        )->willReturn('<html/>');

        $this->assertEquals(null, $this->renderer->renderIndex());
    }

    public function testRenderIndexMorePages()
    {
        $sitemaps = [new Sitemap('pages', 2)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex()->willReturn($sitemaps);

        $this->engine->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => null, 'scheme' => null]
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex());
    }

    public function testRenderIndexMoreProviders()
    {
        $sitemaps = [new Sitemap('pages', 1), new Sitemap('article', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $articleProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(
            ['pages' => $pagesProvider->reveal(), 'article' => $articleProvider->reveal()]
        );
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex()->willReturn($sitemaps);

        $this->engine->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => null, 'scheme' => null]
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex());
    }

    public function testRenderIndexWithSchemeAndDomain()
    {
        $sitemaps = [new Sitemap('pages', 1), new Sitemap('article', 1)];

        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $articleProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(
            ['pages' => $pagesProvider->reveal(), 'article' => $articleProvider->reveal()]
        );
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);
        $this->providerPoolInterface->getIndex()->willReturn($sitemaps);

        $this->engine->render(
            'SuluWebsiteBundle:Sitemap:sitemap-index.xml.twig',
            ['sitemaps' => $sitemaps, 'domain' => 'sulu.lo', 'scheme' => 'http']
        )->willReturn('<html/>');

        $this->assertEquals('<html/>', $this->renderer->renderIndex('sulu.lo', 'http'));
    }

    public function testRenderSitemap()
    {
        $pagesProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->providerPoolInterface->getProviders()->willReturn(['pages' => $pagesProvider->reveal()]);
        $this->providerPoolInterface->hasProvider('pages')->willReturn(true);
        $this->providerPoolInterface->getProvider('pages')->willReturn($pagesProvider);

        $entries = [new SitemapUrl('http://sulu.lo')];
        $pagesProvider->build(1, 'sulu_io', 'en')->willReturn($entries);
        $pagesProvider->getMaxPage()->willReturn(1);

        $webspace = $this->prophesize(Webspace::class);
        $webspace->getKey()->willReturn('sulu');

        $portal = $this->prophesize(Portal::class);
        $portal->getKey()->willReturn('sulu_io');
        $portal->getWebspace()->willReturn($webspace->reveal());
        $portal->getXDefaultLocalization()->willReturn(new Localization('de'));

        $this->engine->render(
            'SuluWebsiteBundle:Sitemap:sitemap.xml.twig',
            [
                'webspaceKey' => 'sulu',
                'locale' => 'en',
                'defaultLocale' => 'de',
                'domain' => 'sulu.lo',
                'scheme' => 'http',
                'entries' => $entries,
            ]
        )->willReturn('<html/>');

        $this->assertEquals(
            '<html/>',
            $this->renderer->renderSitemap('pages', 1, 'en', $portal->reveal(), 'sulu.lo', 'http')
        );
    }
}
