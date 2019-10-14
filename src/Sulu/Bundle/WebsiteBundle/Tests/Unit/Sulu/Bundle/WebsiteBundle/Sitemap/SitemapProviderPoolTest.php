<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Unit\Sulu\Bundle\WebsiteBundle\Sitemap;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\WebsiteBundle\Exception\SitemapProviderNotFoundException;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPool;

class SitemapProviderPoolTest extends TestCase
{
    /**
     * @var SitemapProviderInterface
     */
    public $pagesSitemapProvider;

    /**
     * @var SitemapProviderInterface
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

    public function testGetProvider()
    {
        $this->assertEquals($this->pagesSitemapProvider->reveal(), $this->pool->getProvider('pages'));
    }

    public function testGetProviderNotExists()
    {
        $this->expectException(SitemapProviderNotFoundException::class);

        $this->pool->getProvider('test');
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->pool->hasProvider('pages'));
        $this->assertFalse($this->pool->hasProvider('test'));
    }

    public function testGetIndex()
    {
        $lastMod = new \DateTime();
        $this->pagesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('pages', 1));
        $this->articlesSitemapProvider->createSitemap('http', 'sulu.io')->willReturn(new Sitemap('articles', 1, $lastMod));

        $result = $this->pool->getIndex('http', 'sulu.io');

        $this->assertCount(2, $result);
        $this->assertEquals('pages', $result[0]->getAlias());
        $this->assertNull($result[0]->getLastmod());
        $this->assertEquals('articles', $result[1]->getAlias());
        $this->assertEquals($lastMod, $result[1]->getLastmod());
    }
}
