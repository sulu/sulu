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

namespace Sulu\Page\Tests\Functional\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\DomCrawler\Crawler;

#[CoversNothing]
class PageSeoTest extends SuluTestCase
{
    use CreatePageTrait;

    private function createHomepage(string $uuid, string $webspaceKey): PageInterface
    {
        $homepage = new Page($uuid);
        $homepage->setLft(0);
        $homepage->setRgt(1);
        $homepage->setDepth(0);
        $homepage->setWebspaceKey($webspaceKey);
        self::getEntityManager()->persist($homepage);
        self::getEntityManager()->flush();

        return $homepage;
    }

    public function testCanonicalSelfReferencingForPublishedPage(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'SEO Test Page',
                    'url' => '/seo-test-page',
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/en/seo-test-page');

        $this->assertHttpStatusCode(200, $websiteClient->getResponse());

        $canonical = $crawler->filter('link[rel="canonical"]');
        $this->assertCount(1, $canonical);
        $this->assertSame('http://sulu.io/en/seo-test-page', $canonical->attr('href'));
    }

    public function testEditorCanonicalIsRespected(): void
    {
        self::purgeDatabase();
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Editor Canonical Page',
                    'url' => '/editor-canonical',
                    'parentId' => $homepage->getId(),
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
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Shadow Source Page',
                    'url' => '/shadow-source',
                    'parentId' => $homepage->getId(),
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Shadow Source Page',
                    'url' => '/shadow-source',
                    'parentId' => $homepage->getId(),
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
        $homepage = $this->createHomepage('0199ee04-c220-784e-a6fa-ac985870f2d5', 'sulu-io');

        self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Hreflang Page EN',
                    'url' => '/hreflang-page',
                    'parentId' => $homepage->getId(),
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Hreflang Page DE',
                    'url' => '/hreflang-page',
                    'parentId' => $homepage->getId(),
                ],
            ],
        ]);

        self::ensureKernelShutdown();

        $websiteClient = $this->createWebsiteClient();
        $crawler = $websiteClient->request('GET', 'http://sulu.io/en/hreflang-page');

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
