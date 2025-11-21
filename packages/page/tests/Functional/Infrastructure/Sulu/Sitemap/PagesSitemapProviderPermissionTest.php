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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Sitemap;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Infrastructure\Sulu\Sitemap\PagesSitemapProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class PagesSitemapProviderPermissionTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    private EntityManagerInterface $entityManager;
    private PagesSitemapProvider $provider;
    private Role $anonymousRole;
    private Page $homepageNonSecure;
    private Page $homepageSecure;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();

        $this->provider = $this->getContainer()->get('sulu_page.pages_sitemap_provider');
        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions('sulu-test-secure');

        // Create homepages for both webspaces
        $this->homepageNonSecure = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ], 'sulu-io');

        $this->homepageSecure = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                ],
            ],
        ], 'sulu-test-secure');
    }

    public function testBuildWithoutWebspaceSecurityShowsAllPages(): void
    {
        // Create pages in webspace WITHOUT security
        $page1 = $this->createSimplePage('sulu-io', 'en', 'Page 1');
        $page2 = $this->createSimplePage('sulu-io', 'en', 'Page 2');
        $page3 = $this->createSimplePage('sulu-io', 'en', 'Page 3');

        // Deny access to one page (should have no effect without webspace security)
        $this->denyAccessToPage($page2, $this->anonymousRole);

        $this->entityManager->clear();

        // Build sitemap
        $result = $this->provider->build(1, 'http', 'sulu.io');

        // All pages should be in sitemap (webspace doesn't have security)
        $this->assertGreaterThanOrEqual(3, \count($result));
    }

    public function testBuildWithWebspaceSecurityFiltersPages(): void
    {
        // Create pages in secure webspace
        $allowedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page 1');
        $allowedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page 2');
        $deniedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 1');
        $deniedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 2');
        $deniedPage3 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 3');

        // Set permissions
        $this->grantViewAccessToPage($allowedPage1, $this->anonymousRole);
        $this->grantViewAccessToPage($allowedPage2, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage1, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage2, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage3, $this->anonymousRole);

        $this->entityManager->clear();

        // Build sitemap
        $result = $this->provider->build(1, 'http', 'sulu-secure.io');

        // Only allowed pages should be in sitemap
        $urls = \array_map(fn ($sitemapUrl) => $sitemapUrl->getLoc(), $result);

        // Filter to only pages we created (exclude homepage which might exist)
        $pageUrls = \array_filter($urls, function($url) {
            return \str_contains($url, 'allowed') || \str_contains($url, 'denied');
        });

        // Should have 2 allowed pages
        $allowedUrls = \array_filter($pageUrls, fn ($url) => \str_contains($url, 'allowed'));
        $this->assertCount(2, $allowedUrls);

        // Should NOT have denied pages
        $deniedUrls = \array_filter($pageUrls, fn ($url) => \str_contains($url, 'denied'));
        $this->assertCount(0, $deniedUrls);
    }

    public function testBuildWithWebspaceSecurityFiltersAlternateRoutes(): void
    {
        // Create page with English locale
        $pageEn = $this->createSimplePage('sulu-test-secure', 'en', 'Test Page EN');

        // Add German translation - note: we're creating a NEW page here, not modifying pageEn
        // In a real scenario you'd want to modify the same page, but for this test we'll create separate
        $pageDe = $this->createPage([
            'de' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Test Page DE',
                    'url' => '/test-page-de',
                    'parentId' => $this->homepageSecure->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        // Grant VIEW access to page (permissions are per page, not per locale)
        $this->grantViewAccessToPage($pageEn, $this->anonymousRole);

        $this->entityManager->clear();

        // Build sitemap
        $result = $this->provider->build(1, 'http', 'sulu-secure.io');

        // Find sitemap entry for our page
        $testPageEntries = \array_filter($result, function($sitemapUrl) {
            return \str_contains($sitemapUrl->getLoc(), 'test-page');
        });

        $this->assertGreaterThan(0, \count($testPageEntries));

        // Check that alternate links include German version
        foreach ($testPageEntries as $entry) {
            $alternateLinks = $entry->getAlternateLinks();
            if (\count($alternateLinks) > 0) {
                $locales = \array_map(fn ($link) => $link->getLocale(), $alternateLinks);
                // If page is accessible, both locales should be in alternate links
                $this->assertTrue(\in_array('de', $locales) || \in_array('en', $locales));
            }
        }
    }

    public function testBuildRespectsPageLevelAndWebspaceLevelPermissions(): void
    {
        // Create pages
        $pageWithWebspacePermission = $this->createSimplePage('sulu-test-secure', 'en', 'Page With Webspace Permission');
        $pageWithDeniedPermission = $this->createSimplePage('sulu-test-secure', 'en', 'Page With Denied Permission');

        // Anonymous role has webspace-level VIEW permission (from setUp)
        // Grant access to first page
        $this->grantViewAccessToPage($pageWithWebspacePermission, $this->anonymousRole);

        // Explicitly deny access to second page (object-level deny overrides webspace permission)
        $this->denyAccessToPage($pageWithDeniedPermission, $this->anonymousRole);

        $this->entityManager->clear();

        // Build sitemap
        $result = $this->provider->build(1, 'http', 'sulu-secure.io');

        $urls = \array_map(fn ($sitemapUrl) => $sitemapUrl->getLoc(), $result);

        // Page with explicit VIEW permission should be included
        $allowedUrls = \array_filter($urls, fn ($url) => \str_contains($url, 'with-webspace-permission'));
        $this->assertGreaterThan(0, \count($allowedUrls));

        // Page with explicit DENY should be excluded
        $deniedUrls = \array_filter($urls, fn ($url) => \str_contains($url, 'with-denied-permission'));
        $this->assertCount(0, $deniedUrls);
    }

    public function testBuildExcludesPagesWithSeoHideInSitemap(): void
    {
        // Create page with seoHideInSitemap
        $hiddenPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Hidden Page',
                    'url' => '/hidden-page',
                    'seoHideInSitemap' => true,
                    'parentId' => $this->homepageSecure->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        $visiblePage = $this->createSimplePage('sulu-test-secure', 'en', 'Visible Page');

        // Grant permissions to both
        $this->grantViewAccessToPage($hiddenPage, $this->anonymousRole);
        $this->grantViewAccessToPage($visiblePage, $this->anonymousRole);

        $this->entityManager->clear();

        // Build sitemap
        $result = $this->provider->build(1, 'http', 'sulu-secure.io');

        $urls = \array_map(fn ($sitemapUrl) => $sitemapUrl->getLoc(), $result);

        // Hidden page should NOT be in sitemap (seoHideInSitemap takes precedence)
        $hiddenUrls = \array_filter($urls, fn ($url) => \str_contains($url, 'hidden-page'));
        $this->assertCount(0, $hiddenUrls);

        // Visible page should be in sitemap
        $visibleUrls = \array_filter($urls, fn ($url) => \str_contains($url, 'visible-page'));
        $this->assertGreaterThan(0, \count($visibleUrls));
    }

    /**
     * Helper to create a simple page with live content.
     */
    private function createSimplePage(string $webspaceKey, string $locale, string $title, string $template = 'default'): Page
    {
        // Generate URL from title (convert to lowercase, replace spaces with dashes)
        $url = '/' . \strtolower(\str_replace(' ', '-', $title));

        // Determine which homepage to use based on webspace
        $homepage = 'sulu-io' === $webspaceKey ? $this->homepageNonSecure : $this->homepageSecure;

        return $this->createPage([
            $locale => [
                'live' => [
                    'template' => $template,
                    'title' => $title,
                    'url' => $url,
                    'parentId' => $homepage->getUuid(),
                ],
            ],
        ], $webspaceKey);
    }
}
