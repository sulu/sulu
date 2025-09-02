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

use Sulu\Bundle\HttpCacheBundle\Cache\SuluHttpCache;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapAlternateLink;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderPoolInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class SitemapControllerTest extends WebsiteTestCase
{
    use SetGetPrivatePropertyTrait;

    /**
     * @var RoleInterface
     */
    private $anonymousRole;

    /**
     * @var SitemapProviderPoolInterface
     */
    private $sitemapProviderPool;

    /**
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createWebsiteClient();
        $this->purgeDatabase();

        $this->sitemapProviderPool = self::getContainer()->get('sulu_website.sitemap.pool');
        $this->sitemapProviderPool->reset();

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
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 1, 1),
        ]);

        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('3600', $response->headers->get(SuluHttpCache::HEADER_REVERSE_PROXY_TTL));

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertCount(0, $crawler->filterXPath('//x:urlset/x:url/xhtml:link'));
        $this->assertEquals('http://sulu.lo/en/test-1-0', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testIndexMultipleLanguage(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 1, 1, ['en', 'en-us']),
        ]);

        $crawler = $this->client->request('GET', 'http://test.lo/sitemap.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('3600', $response->headers->get(SuluHttpCache::HEADER_REVERSE_PROXY_TTL));

        $this->assertCount(2, $crawler->filterXPath('//x:urlset/x:url'));

        $this->assertEquals('http://test.lo/en/test-1-0', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc')->text());
        $this->assertEquals(
            'en',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[1]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en/test-1-0',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[1]')->attr('href')
        );
        $this->assertEquals(
            'en-us',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[2]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en-us/test-1-0',
            $crawler->filterXPath('//x:urlset/x:url[1]/xhtml:link[2]')->attr('href')
        );

        $this->assertEquals('http://test.lo/en-us/test-1-0', $crawler->filterXPath('//x:urlset/x:url[2]/x:loc')->text());
        $this->assertEquals(
            'en-us',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[1]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en-us/test-1-0',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[1]')->attr('href')
        );
        $this->assertEquals(
            'en',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[2]')->attr('hreflang')
        );
        $this->assertEquals(
            'http://test.lo/en/test-1-0',
            $crawler->filterXPath('//x:urlset/x:url[2]/xhtml:link[2]')->attr('href')
        );
    }

    public function testSingleProviderRedirect(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 1, 1),
        ]);

        $this->client->request('GET', 'http://sulu.lo/sitemaps/pages.xml');
        $this->assertHttpStatusCode(301, $this->client->getResponse());
    }

    public function testPaginated(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 2, 1),
        ]);

        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/pages-1.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $response = $this->client->getResponse();
        $this->assertHttpStatusCode(200, $response);
        $this->assertSame('3600', $response->headers->get(SuluHttpCache::HEADER_REVERSE_PROXY_TTL));

        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:loc'));
        $this->assertCount(1, $crawler->filterXPath('//x:urlset/x:url/x:lastmod'));
        $this->assertEquals('http://sulu.lo/en/test-1-0', $crawler->filterXPath('//x:urlset/x:url[1]/x:loc[1]')->text());
    }

    public function testPaginatedOverMax(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 2, 1),
        ]);

        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/pages-3.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testNotExistingProvider(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'pages' => $this->createSitemapProvider('pages', 2, 1),
        ]);

        $crawler = $this->client->request('GET', 'http://sulu.lo/sitemaps/test-2.xml');
        $crawler->registerNamespace('x', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $this->assertHttpStatusCode(404, $this->client->getResponse());
    }

    public function testSitemapIndexFile(): void
    {
        self::setPrivateProperty($this->sitemapProviderPool, 'providers', [
            'test' => $this->createSitemapProvider('test', 1, 1),
            'pages' => $this->createSitemapProvider('pages', 1, 1),
        ]);

        $crawler = $this->client->request('GET', 'http://sulu.index/sitemap.xml');
        $this->assertHttpStatusCode(200, $this->client->getResponse());

        $this->assertSame('http://sulu.index/sitemaps/test-1.xml', $crawler->filterXPath('//sitemapindex/sitemap[1]/loc[1]')->text());
        $this->assertSame('http://sulu.index/sitemaps/pages-1.xml', $crawler->filterXPath('//sitemapindex/sitemap[2]/loc[1]')->text());
    }

    /**
     * @param string[] $locales
     */
    private function createSitemapProvider(string $sitemapName, int $pages, int $perPage, array $locales = ['en']): SitemapProviderInterface
    {
        return new class($sitemapName, $pages, $perPage, $locales) implements SitemapProviderInterface {
            /**
             * @param string[] $locales
             */
            public function __construct(private string $sitemapName, private int $pages, private int $perPage, private array $locales)
            {
            }

            public function build($page, $scheme, $host)
            {
                $sitemapUrls = [];
                for ($i = 0; $i < $this->perPage; ++$i) {
                    $defaultLocale = $this->locales[0];
                    foreach ($this->locales as $locale) {
                        $sitemapUrl = new SitemapUrl(
                            $scheme . '://' . $host . '/' . $locale . '/test-' . $page . '-' . $i,
                            $locale,
                            $defaultLocale,
                            new \DateTime('2020-01-01 00:00:00')
                        );

                        foreach ($this->locales as $altLocale) {
                            if ($altLocale !== $locale) {
                                $sitemapUrl->addAlternateLink(new SitemapAlternateLink(
                                    $scheme . '://' . $host . '/' . $altLocale . '/test-' . $page . '-' . $i,
                                    $altLocale
                                ));
                            }
                        }

                        $sitemapUrls[] = $sitemapUrl;
                    }
                }

                return $sitemapUrls;
            }

            public function createSitemap($scheme, $host)
            {
                return new Sitemap($this->sitemapName, $this->pages);
            }

            public function getAlias()
            {
                return $this->sitemapName;
            }

            public function getMaxPage($scheme, $host)
            {
                return $this->pages;
            }
        };
    }
}
