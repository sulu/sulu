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

    public function testIndexSingleLanguage()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://sulu.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertCount(0, $crawler->filterXPath('//x:urlset/x:url/xhtml:link'));
        $this->assertEquals('http://sulu.lo/', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testIndexMultipleLanguage()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://test.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(2, $crawler->filterXPath('//x:urlset/x:url'));

        $this->assertEquals('http://test.lo/en-us', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc')->text());
        $this->assertEquals(
            'en-us',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[1]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en-us',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[1]')->attr('href')
        );
        $this->assertEquals(
            'en',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[2]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[2]')->attr('href')
        );
        $this->assertEquals(
            'x-default',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[3]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[3]')->attr('href')
        );

        $this->assertEquals('http://test.lo/en', $crawler->filterXPath('//x:urlset/x:url[2]/x:loc')->text());
        $this->assertEquals(
            'en',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[1]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[1]')->attr('href')
        );
        $this->assertEquals(
            'en-us',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[2]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en-us',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[2]')->attr('href')
        );
        $this->assertEquals(
            'x-default',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[3]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[3]')->attr('href')
        );

        $this->assertEquals('http://test.lo/en', $crawler->filterXPath('//x:urlset/x:url[2]/x:loc')->text());
    }

    public function testIndexMultipleTlds()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://sulu.com/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertNotContains('sulu.at', $crawler->text());
    }

    public function testProvider()
    {
        $client = $this->createWebsiteClient();
        $client->request('GET', 'http://sulu.lo/sitemaps/pages.xml');
        $this->assertHttpStatusCode(301, $client->getResponse());
    }

    public function testPaginated()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://sulu.lo/sitemaps/pages-1.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertEquals('http://sulu.lo/', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testPaginatedOverMax()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://sulu.lo/sitemaps/pages-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }

    public function testNotExistingProvider()
    {
        $client = $this->createWebsiteClient();
        $crawler = $client->request('GET', 'http://sulu.lo/sitemaps/test-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $client->getResponse());
    }
}
