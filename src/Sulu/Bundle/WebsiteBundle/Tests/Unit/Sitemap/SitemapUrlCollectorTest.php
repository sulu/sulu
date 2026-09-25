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

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sitemap;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\WebsiteBundle\Exception\SitemapProviderNotFoundException;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrlCollector;

class SitemapUrlCollectorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<SitemapProviderPoolInterface>
     */
    private ObjectProphecy $sitemapProviderPool;

    private SitemapUrlCollector $sitemapUrlCollector;

    protected function setUp(): void
    {
        $this->sitemapProviderPool = $this->prophesize(SitemapProviderPoolInterface::class);
        $this->sitemapUrlCollector = new SitemapUrlCollector($this->sitemapProviderPool->reveal());
    }

    public function testCollectAllProviders(): void
    {
        $pageUrl = new SitemapUrl('http://sulu.lo/page', 'en', 'en');
        $articleUrl = new SitemapUrl('http://sulu.lo/article', 'en', 'en');

        $this->sitemapProviderPool->getProviders()->willReturn([
            'pages' => $this->createProvider(1, [$pageUrl]),
            'articles' => $this->createProvider(1, [$articleUrl]),
        ]);

        $result = $this->sitemapUrlCollector->collect('http', 'sulu.lo');

        $this->assertSame([$pageUrl, $articleUrl], $result);
    }

    public function testCollectSingleProvider(): void
    {
        $articleUrl = new SitemapUrl('http://sulu.lo/article', 'en', 'en');

        $this->sitemapProviderPool->getProvider('articles')->willReturn($this->createProvider(1, [$articleUrl]));
        $this->sitemapProviderPool->getProviders()->shouldNotBeCalled();

        $result = $this->sitemapUrlCollector->collect('http', 'sulu.lo', null, 'articles');

        $this->assertSame([$articleUrl], $result);
    }

    public function testCollectUnknownProvider(): void
    {
        $this->sitemapProviderPool->getProvider('unknown')
            ->willThrow(new SitemapProviderNotFoundException('unknown', ['pages']));

        $this->expectException(SitemapProviderNotFoundException::class);

        $this->sitemapUrlCollector->collect('http', 'sulu.lo', null, 'unknown');
    }

    public function testCollectFiltersByLocale(): void
    {
        $germanUrl = new SitemapUrl('http://sulu.lo/de/seite', 'de', 'en');
        $englishUrl = new SitemapUrl('http://sulu.lo/en/page', 'en', 'en');

        $this->sitemapProviderPool->getProviders()->willReturn([
            'pages' => $this->createProvider(1, [$germanUrl, $englishUrl]),
        ]);

        $result = $this->sitemapUrlCollector->collect('http', 'sulu.lo', 'de');

        $this->assertSame([$germanUrl], $result);
    }

    public function testCollectFiltersOtherHosts(): void
    {
        $ownHostUrl = new SitemapUrl('http://sulu.lo/page', 'en', 'en');
        $otherHostUrl = new SitemapUrl('http://other.lo/page', 'en', 'en');

        $this->sitemapProviderPool->getProviders()->willReturn([
            'pages' => $this->createProvider(1, [$ownHostUrl, $otherHostUrl]),
        ]);

        $result = $this->sitemapUrlCollector->collect('http', 'sulu.lo');

        $this->assertSame([$ownHostUrl], $result);
    }

    public function testCollectSkipsProvidersWithoutRequestedPage(): void
    {
        $provider = $this->prophesize(SitemapProviderInterface::class);
        $provider->getMaxPage('http', 'sulu.lo')->willReturn(1);
        $provider->build(2, 'http', 'sulu.lo')->shouldNotBeCalled();

        $this->sitemapProviderPool->getProviders()->willReturn(['pages' => $provider->reveal()]);

        $result = $this->sitemapUrlCollector->collect('http', 'sulu.lo', null, null, 2);

        $this->assertSame([], $result);
    }

    /**
     * @param SitemapUrl[] $sitemapUrls
     */
    private function createProvider(int $maxPage, array $sitemapUrls): SitemapProviderInterface
    {
        $provider = $this->prophesize(SitemapProviderInterface::class);
        $provider->getMaxPage('http', 'sulu.lo')->willReturn($maxPage);
        $provider->build(1, 'http', 'sulu.lo')->willReturn($sitemapUrls);

        return $provider->reveal();
    }
}
