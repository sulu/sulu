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

namespace Sulu\Article\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\DomCrawler\Crawler;

#[CoversNothing]
class ArticleSeoTest extends SuluTestCase
{
    use CreateArticleTrait;

    protected function setUp(): void
    {
        self::bootKernel();

        $requestContext = self::getContainer()->get('router.request_context');
        $requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'sulu-io');
    }

    public function testCanonicalSelfReferencingForPublishedArticle(): void
    {
        self::purgeDatabase();

        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'SEO Test Article',
                    'url' => '/seo-test-article',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/en/seo-test-article');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());

        $canonical = $crawler->filter('link[rel="canonical"]');
        $this->assertCount(1, $canonical);
        $this->assertSame('http://sulu.io/en/seo-test-article', $canonical->attr('href'));
    }

    public function testEditorCanonicalIsRespected(): void
    {
        self::purgeDatabase();

        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Editor Canonical Article',
                    'url' => '/editor-canonical',
                    'mainWebspace' => 'sulu-io',
                    'seo' => [
                        'canonicalUrl' => 'https://example.com/custom-canonical',
                    ],
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/en/editor-canonical');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());

        $canonical = $crawler->filter('link[rel="canonical"]');
        $this->assertCount(1, $canonical);
        $this->assertSame('https://example.com/custom-canonical', $canonical->attr('href'));
    }

    public function testShadowLocaleCanonicalPointsToShadowTarget(): void
    {
        self::purgeDatabase();

        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Shadow Source Article',
                    'url' => '/shadow-source',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Shadow Source Article',
                    'url' => '/shadow-source',
                    'mainWebspace' => 'sulu-io',
                    'shadowOn' => true,
                    'shadowLocale' => 'en',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/de/shadow-source');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());

        $canonical = $crawler->filter('link[rel="canonical"]');
        $this->assertCount(1, $canonical);
        $canonicalHref = $canonical->attr('href');
        $this->assertNotNull($canonicalHref);
        $this->assertStringContainsString('/en/shadow-source', $canonicalHref);
    }

    public function testHreflangAlternatesAreAbsoluteUrls(): void
    {
        self::purgeDatabase();

        self::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Hreflang Article EN',
                    'url' => '/hreflang-article',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Hreflang Article DE',
                    'url' => '/hreflang-article',
                    'mainWebspace' => 'sulu-io',
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/en/hreflang-article');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());

        $alternates = $crawler->filter('link[rel="alternate"]');
        $this->assertGreaterThanOrEqual(2, $alternates->count());

        $alternates->each(function(Crawler $node): void {
            $href = $node->attr('href');
            $this->assertNotNull($href);
            $this->assertMatchesRegularExpression('#^https?://#', $href, 'Hreflang URL must be absolute: ' . $href);
        });
    }
}
