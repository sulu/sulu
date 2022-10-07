<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Tests\Functional\Controller;

use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SitemapControllerTest extends WebsiteTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        $this->purgeDatabase();
        $this->initPhpcr();

        $this->getContainer()->get('sulu_security.system_store')->setSystem('sulu_io');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->anonymousRole = $this->getContainer()->get('sulu.repository.role')->createNew();
        $this->anonymousRole->setName('Anonymous');
        $this->anonymousRole->setAnonymous(true);
        $this->anonymousRole->setSystem('sulu_io');

        $permission = new Permission();
        $permission->setPermissions(122);
        $permission->setRole($this->anonymousRole);
        $permission->setContext('sulu.webspaces.sulu_io');

        $em->persist($permission);
        $em->persist($this->anonymousRole);
        $em->flush();
    }

    public function testIndexSingleLanguage(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertCount(0, $crawler->filterXPath('//x:urlset/x:url/xhtml:link'));
        $this->assertEquals('http://sulu.lo/', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testIndexMultipleLanguage(): void
    {
        $crawler = $this->client->request('GET', 'http://test.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

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

        $this->assertEquals('http://test.lo/en', $crawler->filterXPath('//x:urlset/x:url[2]/x:loc')->text());
    }

    public function testIndexMultipleTlds(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.com/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertStringNotContainsString('sulu.at', $crawler->text());
    }

    public function testProvider(): void
    {
        $this->client->request('GET', 'http://sulu.lo/sitemaps/pages.xml');
        $this->assertHttpStatusCode(301, $this->client->getResponse());
    }

    public function testPaginated(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/pages-1.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertEquals('http://sulu.lo/', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testPaginatedOverMax(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/pages-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testNotExistingProvider(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/test-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testSitemapIndexFile(): void
    {
        $crawler = $this->client->request('GET', 'http://sulu.index/sitemap.xml');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame('http://sulu.index/sitemaps/test-1.xml', $crawler->filterXPath('//sitemapindex/sitemap[1]/loc[1]')->text());
        $this->assertSame('http://sulu.index/sitemaps/pages-1.xml', $crawler->filterXPath('//sitemapindex/sitemap[2]/loc[1]')->text());
    }
}
