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
use Sulu\Bundle\WebsiteBundle\Exception\SitemapProviderNotFoundException;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPool;

class SitemapProviderPoolTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var mixed[]
     */
    public $providers;

    /**
     * @var ObjectProphecy<SitemapProviderInterface>
     */
    public $pagesSitemapProvider;

    /**
     * @var ObjectProphecy<SitemapProviderInterface>
     */
    public $articlesSitemapProvider;

    /**
     * @var SitemapProviderPool
     */
    public $pool;

    public function setUp(): void
    {
        $this->pagesSitemapProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->pagesSitemapProvider->getAlias()->willReturn('pages');
        $this->articlesSitemapProvider = $this->prophesize(SitemapProviderInterface::class);
        $this->articlesSitemapProvider->getAlias()->willReturn('articles');

        $this->providers = [
            $this->prophesize(SitemapProviderInterface::class)->getAlias(),
            $this->prophesize(SitemapProviderInterface::class)->getAlias()->willReturn('articles'),
        ];

        $this->pool = new SitemapProviderPool([
            $this->pagesSitemapProvider->reveal(),
            $this->articlesSitemapProvider->reveal(),
        ]);
    }

    public function testGetProvider(): void
    {
        $this->assertEquals($this->pagesSitemapProvider->reveal(), $this->pool->getProvider('pages'));
    }

    public function testGetProviderNotExists(): void
    {
        $this->expectException(SitemapProviderNotFoundException::class);

        $this->pool->getProvider('test');
    }

    public function testHasProvider(): void
    {
        $this->assertTrue($this->pool->hasProvider('pages'));
        $this->assertFalse($this->pool->hasProvider('test'));
    }

    public function testGetIndex(): void
    {
        $suluLastMod = new \DateTime();
        $this->pagesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('pages', 1))
            ->shouldBeCalled();
        $this->articlesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('articles', 1, $suluLastMod))
            ->shouldBeCalled();

        $exampleLastMod = new \DateTime('-1 week');
        $this->pagesSitemapProvider->createSitemap('http', 'example.localhost')->willReturn(new Sitemap('pages-example', 1))
            ->shouldBeCalled();
        $this->articlesSitemapProvider->createSitemap('http', 'example.localhost')->willReturn(new Sitemap('articles-example', 1, $exampleLastMod))
            ->shouldBeCalled();

        $result = $this->pool->getIndex('http', 'sulu.io');

        $this->assertCount(2, $result);
        $this->assertEquals('pages', $result[0]->getAlias());
        $this->assertNull($result[0]->getLastmod());
        $this->assertEquals('articles', $result[1]->getAlias());
        $this->assertEquals($suluLastMod, $result[1]->getLastmod());

        $result = $this->pool->getIndex('http', 'example.localhost');
        $this->assertCount(2, $result);
        $this->assertEquals('pages-example', $result[0]->getAlias());
        $this->assertNull($result[0]->getLastmod());
        $this->assertEquals('articles-example', $result[1]->getAlias());
        $this->assertEquals($exampleLastMod, $result[1]->getLastmod());
    }

    public function testReset(): void
    {
        $suluLastMod = new \DateTime();
        $this->pagesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('pages', 1))
            ->shouldBeCalledTimes(2);
        $this->articlesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('articles', 1, $suluLastMod))
            ->shouldBeCalledTimes(2);

        $result = $this->pool->getIndex('http', 'sulu.io');
        $this->assertCount(2, $result);
        $result = $this->pool->getIndex('http', 'sulu.io');
        $this->assertCount(2, $result, 'Loaded from cache is same as before.');

        $this->pool->reset();

        $result = $this->pool->getIndex('http', 'sulu.io');
        $this->assertCount(2, $result);
    }
}
