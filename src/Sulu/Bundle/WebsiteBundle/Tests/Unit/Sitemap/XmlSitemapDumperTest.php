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
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumper;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapDumperInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\XmlSitemapRendererInterface;
use Symfony\Component\Filesystem\Filesystem;

class XmlSitemapDumperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<XmlSitemapRendererInterface>
     */
    protected $renderer;

    /**
     * @var ObjectProphecy<SitemapProviderPoolInterface>
     */
    protected $providerPool;

    /**
     * @var ObjectProphecy<Filesystem>
     */
    protected $filesystem;

    /**
     * @var XmlSitemapDumperInterface
     */
    protected $dumper;

    protected function setUp(): void
    {
        $this->renderer = $this->prophesize(XmlSitemapRendererInterface::class);
        $this->providerPool = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->filesystem = $this->prophesize(Filesystem::class);

        $this->dumper = new XmlSitemapDumper(
            $this->renderer->reveal(),
            $this->providerPool->reveal(),
            $this->filesystem->reveal(),
            '/'
        );
    }

    public function testGetDumpPath(): void
    {
        $this->assertEquals(
            '/http/sulu.lo/sitemaps/pages-1.xml',
            $this->dumper->getDumpPath('http', 'sulu.lo', 'pages', 1)
        );
    }

    public function testGetIndexDumpPath(): void
    {
        $this->assertEquals(
            '/http/sulu.lo/sitemap.xml',
            $this->dumper->getIndexDumpPath('http', 'sulu.lo')
        );
    }

    public function testDumpHost(): void
    {
        $this->renderer->renderIndex('http', 'sulu.io')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $providers = [
            'test-1' => $this->prophesize(SitemapProviderInterface::class),
            'test-2' => $this->prophesize(SitemapProviderInterface::class),
        ];

        foreach ($providers as $alias => $provider) {
            $this->providerPool->getProvider($alias)->willReturn($provider->reveal());

            $provider->getMaxPage('http', 'sulu.io')->willReturn(1);
            $this->renderer
                ->renderSitemap($alias, 1, 'http', 'sulu.io')
                ->willReturn('<sitemap-' . $alias . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu.io', $alias, 1),
                '<sitemap-' . $alias . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(
            \array_map(
                function($provider) {
                    return $provider->reveal();
                },
                $providers
            )
        );

        $this->dumper->dumpHost('http', 'sulu.io');
    }

    public function testDumpPortalInformationNoIndex(): void
    {
        $this->renderer->renderIndex('http', 'sulu.io')->willReturn(null);

        $provider = $this->prophesize(SitemapProviderInterface::class);

        $this->renderer
            ->renderSitemap('test-1', 1, 'http', 'sulu.io')
            ->willReturn('<sitemap/>');
        $this->filesystem->dumpFile(
            $this->dumper->getDumpPath('http', 'sulu.io', 'test-1', 1),
            Argument::any()
        )->shouldNotBeCalled();
        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu.io'),
            '<sitemap/>'
        )->shouldBeCalled();

        $this->providerPool->getProviders()->willReturn(['test-1' => $provider->reveal()]);

        $this->dumper->dumpHost('http', 'sulu.io');
    }

    public function testDumpHostWildcard(): void
    {
        $this->renderer->renderIndex('http', 'sulu.io')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $providers = [
            'test-1' => $this->prophesize(SitemapProviderInterface::class),
            'test-2' => $this->prophesize(SitemapProviderInterface::class),
        ];

        foreach ($providers as $alias => $provider) {
            $this->providerPool->getProvider($alias)->willReturn($provider->reveal());

            $provider->getMaxPage('http', 'sulu.io')->willReturn(1);
            $this->renderer
                ->renderSitemap($alias, 1, 'http', 'sulu.io')
                ->willReturn('<sitemap-' . $alias . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu.io', $alias, 1),
                '<sitemap-' . $alias . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(
            \array_map(
                function($provider) {
                    return $provider->reveal();
                },
                $providers
            )
        );

        $this->dumper->dumpHost('http', 'sulu.io');
    }

    public function testDumpPortalInformationMultiplePages(): void
    {
        $this->renderer->renderIndex('http', 'sulu.io')->willReturn('<sitemapindex/>');

        $this->filesystem->dumpFile(
            $this->dumper->getIndexDumpPath('http', 'sulu.io'),
            '<sitemapindex/>'
        )->shouldBeCalled();

        $provider = $this->prophesize(SitemapProviderInterface::class);

        $this->providerPool->getProvider('test-1')->willReturn($provider->reveal());

        $provider->getMaxPage('http', 'sulu.io')->willReturn(2);
        for ($page = 1; $page <= 2; ++$page) {
            $this->renderer
                ->renderSitemap('test-1', $page, 'http', 'sulu.io')
                ->willReturn('<sitemap-' . $page . '/>');
            $this->filesystem->dumpFile(
                $this->dumper->getDumpPath('http', 'sulu.io', 'test-1', $page),
                '<sitemap-' . $page . '/>'
            )->shouldBeCalled();
        }

        $this->providerPool->getProviders()->willReturn(['test-1' => $provider->reveal()]);

        $this->dumper->dumpHost('http', 'sulu.io');
    }
}
