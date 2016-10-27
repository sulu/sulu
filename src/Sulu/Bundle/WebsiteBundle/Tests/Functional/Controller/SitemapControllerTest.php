<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SitemapControllerTest extends SuluTestCase
{
    public function setUp()
    {
        $this->initPhpcr();
    }

    public function testIndex()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', '/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertEquals('http://localhost', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testProvider()
    {
        $client = $this->createWebsiteClient();
        $client->request('GET', '/sitemaps/pages.xml');
        $this->assertHttpStatusCode(301, $client->getResponse());
    }

    public function testPaginated()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', '/sitemaps/pages-1.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertEquals('http://localhost', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testPaginatedOverMax()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', '/sitemaps/pages-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testNotExistingProvider()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', '/sitemaps/test-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }
}
