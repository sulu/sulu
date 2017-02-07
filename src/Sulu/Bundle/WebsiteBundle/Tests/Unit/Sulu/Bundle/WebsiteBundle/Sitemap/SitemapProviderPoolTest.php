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

use Sulu\Bundle\WebsiteBundle\Exception\SitemapProviderNotFoundException;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPool;

class SitemapProviderPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SitemapProviderInterface[]
     */
    public $providers;

    /**
     * @var SitemapProviderPool
     */
    public $pool;

    public function setUp()
    {
        $this->providers = [
            'pages' => $this->prophesize(SitemapProviderInterface::class),
            'articles' => $this->prophesize(SitemapProviderInterface::class),
        ];

        $this->pool = new SitemapProviderPool(
            array_map(
                function ($prophet) {
                    return $prophet->reveal();
                },
                $this->providers
            )
        );
    }

    public function testGetProvider()
    {
        $this->assertEquals($this->providers['pages']->reveal(), $this->pool->getProvider('pages'));
    }

    public function testGetProviderNotExists()
    {
        $this->setExpectedException(SitemapProviderNotFoundException::class);

        $this->pool->getProvider('test');
    }

    public function testHasProvider()
    {
        $this->assertTrue($this->pool->hasProvider('pages'));
        $this->assertFalse($this->pool->hasProvider('test'));
    }

    public function testGetIndex()
    {
        $this->providers['pages']->createSitemap('pages')->willReturn(new Sitemap('pages', 1));
        $this->providers['articles']->createSitemap('articles')->willReturn(new Sitemap('articles', 1));

        $result = $this->pool->getIndex();

        $this->assertCount(2, $result);
        $this->assertEquals('pages', $result[0]->getAlias());
        $this->assertEquals('articles', $result[1]->getAlias());
    }
}
