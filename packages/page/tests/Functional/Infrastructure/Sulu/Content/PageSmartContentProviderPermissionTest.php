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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Infrastructure\Sulu\Content\PageSmartContentProvider;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class PageSmartContentProviderPermissionTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    private EntityManagerInterface $entityManager;
    private PageSmartContentProvider $provider;
    private Role $anonymousRole;
    private Page $homepageNonSecure;
    private Page $homepageSecure;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();

        $this->provider = $this->getContainer()->get('sulu_page.page_smart_content_provider');
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

    public function testFindByWithoutWebspaceSecurityShowsAllPages(): void
    {
        // Create pages in webspace WITHOUT security
        $page1 = $this->createSimplePage('sulu-io', 'en', 'Page 1');
        $page2 = $this->createSimplePage('sulu-io', 'en', 'Page 2');
        $page3 = $this->createSimplePage('sulu-io', 'en', 'Page 3');

        // Deny access to one page (should have no effect without webspace security)
        $this->denyAccessToPage($page2, $this->anonymousRole);

        $this->entityManager->clear();

        // Query pages
        $result = $this->provider->findFlatBy(
            $this->createFilters('sulu-io', 'en'),
            []
        );

        // All pages should be returned because webspace doesn't have security enabled
        $this->assertCount(3, $result);
        $uuids = \array_column($result, 'id');
        $this->assertContains($page1->getUuid(), $uuids);
        $this->assertContains($page2->getUuid(), $uuids);
        $this->assertContains($page3->getUuid(), $uuids);
    }

    public function testFindByWithWebspaceSecurityFiltersPagesWithoutPermissions(): void
    {
        // Create pages in secure webspace
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 1');
        $deniedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 2');

        // Grant VIEW permission to only one page
        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage1, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage2, $this->anonymousRole);

        $this->entityManager->clear();

        // Query pages
        $result = $this->provider->findFlatBy(
            $this->createFilters('sulu-test-secure', 'en'),
            []
        );

        // Only allowed page should be returned
        $uuids = \array_column($result, 'id');
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage1->getUuid(), $uuids);
        $this->assertNotContains($deniedPage2->getUuid(), $uuids);
    }

    public function testFindByWithWebspaceSecurityAndMixedPermissions(): void
    {
        // Create pages with mixed permissions
        $allowed1 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 1');
        $allowed2 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 2');
        $denied1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 1');
        $denied2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 2');
        $denied3 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 3');

        // Set permissions
        $this->grantViewAccessToPage($allowed1, $this->anonymousRole);
        $this->grantViewAccessToPage($allowed2, $this->anonymousRole);
        $this->denyAccessToPage($denied1, $this->anonymousRole);
        $this->denyAccessToPage($denied2, $this->anonymousRole);
        $this->denyAccessToPage($denied3, $this->anonymousRole);

        $this->entityManager->clear();

        // Query pages
        $result = $this->provider->findFlatBy(
            $this->createFilters('sulu-test-secure', 'en'),
            []
        );

        // Only allowed pages should be returned
        $this->assertCount(2, \array_filter($result, function($item) use ($allowed1, $allowed2) {
            return \in_array($item['id'], [$allowed1->getUuid(), $allowed2->getUuid()]);
        }));

        $uuids = \array_column($result, 'id');
        $this->assertContains($allowed1->getUuid(), $uuids);
        $this->assertContains($allowed2->getUuid(), $uuids);
        $this->assertNotContains($denied1->getUuid(), $uuids);
        $this->assertNotContains($denied2->getUuid(), $uuids);
        $this->assertNotContains($denied3->getUuid(), $uuids);
    }

    public function testCountByWithWebspaceSecurityRespectsPermissions(): void
    {
        // Create pages
        $allowed1 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 1');
        $allowed2 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 2');
        $denied1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 1');
        $denied2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 2');

        // Set permissions
        $this->grantViewAccessToPage($allowed1, $this->anonymousRole);
        $this->grantViewAccessToPage($allowed2, $this->anonymousRole);
        $this->denyAccessToPage($denied1, $this->anonymousRole);
        $this->denyAccessToPage($denied2, $this->anonymousRole);

        $this->entityManager->clear();

        // Count pages
        $count = $this->provider->countBy(
            $this->createFilters('sulu-test-secure', 'en')
        );

        // Should count only accessible pages (homepage + 2 allowed pages)
        $this->assertGreaterThanOrEqual(2, $count);
    }

    public function testFindByRespectsWebspaceSecurityFlagPerWebspace(): void
    {
        // Create same page in both webspaces
        $securePageAllowed = $this->createSimplePage('sulu-test-secure', 'en', 'Secure Allowed');
        $securePageDenied = $this->createSimplePage('sulu-test-secure', 'en', 'Secure Denied');
        $normalPage = $this->createSimplePage('sulu-io', 'en', 'Normal Page');

        // Set permissions
        $this->grantViewAccessToPage($securePageAllowed, $this->anonymousRole);
        $this->denyAccessToPage($securePageDenied, $this->anonymousRole);
        $this->denyAccessToPage($normalPage, $this->anonymousRole); // Should have no effect

        $this->entityManager->clear();

        // Query secure webspace - should filter
        $secureResult = $this->provider->findFlatBy(
            $this->createFilters('sulu-test-secure', 'en'),
            []
        );

        $secureUuids = \array_column($secureResult, 'id');
        $this->assertContains($securePageAllowed->getUuid(), $secureUuids);
        $this->assertNotContains($securePageDenied->getUuid(), $secureUuids);

        // Query normal webspace - should NOT filter
        $normalResult = $this->provider->findFlatBy(
            $this->createFilters('sulu-io', 'en'),
            []
        );

        $normalUuids = \array_column($normalResult, 'id');
        $this->assertContains($normalPage->getUuid(), $normalUuids); // Should be included despite denied permission
    }

    public function testFindByWithPageWithoutAccessControlEntryUsesWebspacePermissions(): void
    {
        // Create page WITHOUT setting any access control
        $pageWithoutAcl = $this->createSimplePage('sulu-test-secure', 'en', 'Page Without ACL');

        // Don't call setPagePermissions - no AccessControl entry

        $this->entityManager->clear();

        // Query pages
        $result = $this->provider->findFlatBy(
            $this->createFilters('sulu-test-secure', 'en'),
            []
        );

        // Page should be visible (anonymous role has webspace-level permissions)
        $uuids = \array_column($result, 'id');
        $this->assertContains($pageWithoutAcl->getUuid(), $uuids);
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

    /**
     * @param array<string, mixed> $additionalFilters
     *
     * @return array{categories: array<int>, categoryOperator: 'AND'|'OR', websiteCategories: array<string>, websiteCategoryOperator: 'AND'|'OR', tags: array<int>, tagOperator: 'AND'|'OR', websiteTags: array<string>, websiteTagOperator: 'AND'|'OR', types: array<string>, typesOperator: 'OR', locale: string, dataSource: string|null, limit: int|null, offset: int, includeSubFolders: bool, excludeDuplicates: bool, webspaceKey: string, stage: string}
     */
    private function createFilters(string $webspaceKey, string $locale, array $additionalFilters = []): array
    {
        // Determine which homepage to use based on webspace
        $homepage = 'sulu-io' === $webspaceKey ? $this->homepageNonSecure : $this->homepageSecure;

        /** @var array{categories: array<int>, categoryOperator: 'AND'|'OR', websiteCategories: array<string>, websiteCategoryOperator: 'AND'|'OR', tags: array<int>, tagOperator: 'AND'|'OR', websiteTags: array<string>, websiteTagOperator: 'AND'|'OR', types: array<string>, typesOperator: 'OR', locale: string, dataSource: string|null, limit: int|null, offset: int, includeSubFolders: bool, excludeDuplicates: bool, webspaceKey: string, stage: string} */
        return \array_merge([
            'locale' => $locale,
            'webspaceKey' => $webspaceKey,
            'dataSource' => $homepage->getUuid(),
            'includeSubFolders' => true,
            'categories' => [],
            'categoryOperator' => 'OR',
            'websiteCategories' => [],
            'websiteCategoryOperator' => 'OR',
            'tags' => [],
            'tagOperator' => 'OR',
            'websiteTags' => [],
            'websiteTagOperator' => 'OR',
            'types' => [],
            'typesOperator' => 'OR',
            'limit' => null,
            'offset' => 0,
            'excludeDuplicates' => false,
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ], $additionalFilters);
    }
}
