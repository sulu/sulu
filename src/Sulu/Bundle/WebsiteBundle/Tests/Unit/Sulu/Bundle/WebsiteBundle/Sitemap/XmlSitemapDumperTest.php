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

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumper;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumperInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRendererInterface;
use Sulu\Component\Webspace\Portal;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\Filesystem\Filesystem;

class XmlSitemapDumperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var XmlSitemapRendererInterface
     */
    protected $renderer;

    /**
     * @var SitemapProviderPoolInterface
     */
    protected $providerPool;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var XmlSitemapDumperInterface
     */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->renderer = $this->prophesize(XmlSitemapRendererInterface::class);
        $this->providerPool = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->dumper = new XmlSitemapDumper(
            $this->renderer->reveal(),
            $this->providerPool->reveal(),
            $this->filesystem->reveal(),
            '/',
            'sulu.io'
        );
    }

    public function testGetDumpPath()
    {
        $this->assertEquals(
            '/http/sulu_io/en/sulu.lo/sitemaps/pages-1.xml',
            $this->dumper->getDumpPath('http', 'sulu_io', 'en', 'sulu.lo', 'pages', 1)
        );
    }

    public function testGetIndexDumpPath()
    {
        $this->assertEquals(
            '/http/sulu_io/en/sulu.lo/sitemap.xml',
            $this->dumper->getIndexDumpPath('http', 'sulu_io', 'en', 'sulu.lo')
        );
    }

    public function testDumpPortalInformation()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('sulu.io/{localization}');
        $portalInformation->getHost()->willReturn('sulu.io');
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $portalInformation->getLocale()->willReturn('de');
        $portalInformation->getPortal()->willReturn(new Portal());

        $this->renderer->renderIndex('sulu.io', 'http')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu_io', 'de', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $providers = [
            'test-1' => $this->prophesize(SitemapProviderInterface::class),
            'test-2' => $this->prophesize(SitemapProviderInterface::class),
        ];

        foreach ($providers as $alias => $provider) {
            $this->providerPool->getProvider($alias)->willReturn($provider->reveal());

            $provider->getMaxPage()->willReturn(1);
            $this->renderer
                ->renderSitemap($alias, 1, 'de', Argument::type(Portal::class), 'sulu.io', 'http')
                ->willReturn('<sitemap-' . $alias . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu_io', 'de', 'sulu.io', $alias, 1),
                '<sitemap-' . $alias . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(
            array_map(
                function ($provider) {
                    return $provider->reveal();
                },
                $providers
            )
        );

        $this->dumper->dumpPortalInformation($portalInformation->reveal(), 'http');
    }

    public function testDumpPortalInformationNoIndex()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('sulu.io/{localization}');
        $portalInformation->getHost()->willReturn('sulu.io');
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $portalInformation->getLocale()->willReturn('de');
        $portalInformation->getPortal()->willReturn(new Portal());

        $this->renderer->renderIndex('sulu.io', 'http')->willReturn(null);


        $provider = $this->prophesize(SitemapProviderInterface::class);

        $this->renderer
            ->renderSitemap('test-1', 1, 'de', Argument::type(Portal::class), 'sulu.io', 'http')
            ->willReturn('<sitemap/>');
        $this->filesystem->dumpFile(
            $this->dumper->getDumpPath('http', 'sulu_io', 'de', 'sulu.io', 'test-1', 1),
            Argument::any()
        )->shouldNotBeCalled();
        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu_io', 'de', 'sulu.io'),
            '<sitemap/>'
        )->shouldBeCalled();

        $this->providerPool->getProviders()->willReturn(['test-1' => $provider->reveal()]);

        $this->dumper->dumpPortalInformation($portalInformation->reveal(), 'http');
    }

    public function testDumpPortalInformationWildcard()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('{host}/{localization}');
        $portalInformation->getHost()->willReturn('sulu.io');
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $portalInformation->getLocale()->willReturn('de');
        $portalInformation->getPortal()->willReturn(new Portal());
        $portalInformation->setUrl('sulu.io/{localization}')->shouldBeCalled();

        $this->renderer->renderIndex('sulu.io', 'http')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu_io', 'de', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $providers = [
            'test-1' => $this->prophesize(SitemapProviderInterface::class),
            'test-2' => $this->prophesize(SitemapProviderInterface::class),
        ];

        foreach ($providers as $alias => $provider) {
            $this->providerPool->getProvider($alias)->willReturn($provider->reveal());

            $provider->getMaxPage()->willReturn(1);
            $this->renderer
                ->renderSitemap($alias, 1, 'de', Argument::type(Portal::class), 'sulu.io', 'http')
                ->willReturn('<sitemap-' . $alias . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu_io', 'de', 'sulu.io', $alias, 1),
                '<sitemap-' . $alias . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(
            array_map(
                function ($provider) {
                    return $provider->reveal();
                },
                $providers
            )
        );

        $this->dumper->dumpPortalInformation($portalInformation->reveal(), 'http');
    }

    public function testDumpPortalInformationMultiplePages()
    {
        $portalInformation = $this->prophesize(PortalInformation::class);
        $portalInformation->getUrl()->willReturn('sulu.io/{localization}');
        $portalInformation->getHost()->willReturn('sulu.io');
        $portalInformation->getWebspaceKey()->willReturn('sulu_io');
        $portalInformation->getLocale()->willReturn('de');
        $portalInformation->getPortal()->willReturn(new Portal());

        $this->renderer->renderIndex('sulu.io', 'http')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu_io', 'de', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $provider = $this->prophesize(SitemapProviderInterface::class);

        $this->providerPool->getProvider('test-1')->willReturn($provider->reveal());

        $provider->getMaxPage()->willReturn(2);
        for ($page = 1; $page <= 2; ++$page) {
            $this->renderer
                ->renderSitemap('test-1', $page, 'de', Argument::type(Portal::class), 'sulu.io', 'http')
                ->willReturn('<sitemap-' . $page . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu_io', 'de', 'sulu.io', 'test-1', $page),
                '<sitemap-' . $page . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(['test-1' => $provider->reveal()]);

        $this->dumper->dumpPortalInformation($portalInformation->reveal(), 'http');
    }
}
