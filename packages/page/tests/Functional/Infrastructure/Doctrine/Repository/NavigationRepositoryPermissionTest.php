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

namespace Sulu\Page\Tests\Functional\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class NavigationRepositoryPermissionTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    private EntityManagerInterface $entityManager;
    private NavigationRepositoryInterface $navigationRepository;
    private Role $anonymousRole;
    private Page $homepageNonSecure;
    private Page $homepageSecure;

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(): array
    {
        return [
            'uuid' => 'object.resource.id',
            'title' => 'title',
            'url' => 'url',
            'webspaceKey' => 'object.resource.webspaceKey',
        ];
    }

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();
        $this->navigationRepository = $this->getContainer()->get('sulu_page.navigation_repository');
        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions('sulu-test-secure');

        $systemStore = $this->getContainer()->get('sulu_security.system_store');
        $systemStore->setSystem('sulu-test-secure');

        $this->homepageNonSecure = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                    'navigationContexts' => ['main'],
                ],
            ],
        ], 'sulu-io');

        $this->homepageSecure = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Homepage',
                    'url' => '/',
                    'navigationContexts' => ['main'],
                ],
            ],
        ], 'sulu-test-secure');
    }

    public function testNavigationFlatWithoutWebspaceSecurityShowsAllPages(): void
    {
        $page1 = $this->createSimplePage('sulu-io', 'en', 'Page 1');
        $page2 = $this->createSimplePage('sulu-io', 'en', 'Page 2');
        $page3 = $this->createSimplePage('sulu-io', 'en', 'Page 3');

        $this->denyAccessToPage($page2, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-io',
            null,
            1,
            $this->getDefaultProperties()
        );

        $this->assertCount(4, $result);
        $uuids = \array_column($result, 'uuid');
        $this->assertContains($page1->getUuid(), $uuids);
        $this->assertContains($page2->getUuid(), $uuids);
        $this->assertContains($page3->getUuid(), $uuids);
    }

    public function testNavigationFlatWithWebspaceSecurityFiltersDeniedPages(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 1');
        $deniedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 2');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage1, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage2, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-test-secure',
            null,
            1,
            $this->getDefaultProperties()
        );

        $uuids = \array_column($result, 'uuid');
        $this->assertCount(2, $result);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage1->getUuid(), $uuids);
        $this->assertNotContains($deniedPage2->getUuid(), $uuids);
    }

    public function testNavigationTreeWithWebspaceSecurityFiltersDeniedPages(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-test-secure',
            null,
            2,
            $this->getDefaultProperties()
        );

        $uuids = $this->collectTreeUuids($result);
        $this->assertCount(2, $uuids);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage->getUuid(), $uuids);
    }

    public function testNavigationFlatByUuidWithWebspaceSecurityFiltersDeniedPages(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationFlatByUuid(
            $this->homepageSecure->getUuid(),
            'en',
            'sulu-test-secure',
            1,
            'main',
            $this->getDefaultProperties()
        );

        $uuids = \array_column($result, 'uuid');
        $this->assertCount(1, $result);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage->getUuid(), $uuids);
    }

    public function testNavigationTreeByUuidWithWebspaceSecurityFiltersDeniedPages(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->homepageSecure->getUuid(),
            'en',
            'sulu-test-secure',
            1,
            'main',
            $this->getDefaultProperties()
        );

        $uuids = \array_column($result, 'uuid');
        $this->assertCount(1, $result);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage->getUuid(), $uuids);
    }

    public function testNavigationTreeWithMixedPermissions(): void
    {
        $allowed1 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 1');
        $allowed2 = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed 2');
        $denied1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 1');
        $denied2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 2');

        $this->grantViewAccessToPage($allowed1, $this->anonymousRole);
        $this->grantViewAccessToPage($allowed2, $this->anonymousRole);
        $this->denyAccessToPage($denied1, $this->anonymousRole);
        $this->denyAccessToPage($denied2, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->homepageSecure->getUuid(),
            'en',
            'sulu-test-secure',
            1,
            'main',
            $this->getDefaultProperties()
        );

        $uuids = \array_column($result, 'uuid');
        $this->assertCount(2, $result);
        $this->assertContains($allowed1->getUuid(), $uuids);
        $this->assertContains($allowed2->getUuid(), $uuids);
        $this->assertNotContains($denied1->getUuid(), $uuids);
        $this->assertNotContains($denied2->getUuid(), $uuids);
    }

    public function testNavigationTreeWithWebspaceSecurityFiltersTreeChildren(): void
    {
        $parentPage = $this->createSimplePage('sulu-test-secure', 'en', 'Parent Page');

        $allowedChild = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Allowed Child',
                    'url' => '/parent-page/allowed-child',
                    'navigationContexts' => ['main'],
                    'parentId' => $parentPage->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        $deniedChild = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Denied Child',
                    'url' => '/parent-page/denied-child',
                    'navigationContexts' => ['main'],
                    'parentId' => $parentPage->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        $this->grantViewAccessToPage($parentPage, $this->anonymousRole);
        $this->grantViewAccessToPage($allowedChild, $this->anonymousRole);
        $this->denyAccessToPage($deniedChild, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->homepageSecure->getUuid(),
            'en',
            'sulu-test-secure',
            2,
            'main',
            $this->getDefaultProperties()
        );

        $parentInResult = null;
        foreach ($result as $item) {
            if ($item['uuid'] === $parentPage->getUuid()) {
                $parentInResult = $item;
                break;
            }
        }

        $this->assertNotNull($parentInResult, 'Parent page should be in navigation');
        $this->assertIsArray($parentInResult['children']);

        $childUuids = \array_column($parentInResult['children'], 'uuid');
        $this->assertCount(1, $childUuids);
        $this->assertContains($allowedChild->getUuid(), $childUuids);
        $this->assertNotContains($deniedChild->getUuid(), $childUuids);
    }

    public function testNavigationWithPageWithoutAccessControlEntryUsesWebspacePermissions(): void
    {
        $pageWithoutAcl = $this->createSimplePage('sulu-test-secure', 'en', 'Page Without ACL');
        $deniedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page');
        $this->denyAccessToPage($deniedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-test-secure',
            null,
            1,
            $this->getDefaultProperties()
        );

        $uuids = \array_column($result, 'uuid');
        $this->assertCount(2, $result);
        $this->assertContains($pageWithoutAcl->getUuid(), $uuids);
        $this->assertNotContains($deniedPage->getUuid(), $uuids);
    }

    public function testBreadcrumbShowsAllAncestorsRegardlessOfPermissions(): void
    {
        $parentPage = $this->createSimplePage('sulu-test-secure', 'en', 'Parent Page');

        $childPage = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Child Page',
                    'url' => '/parent-page/child-page',
                    'navigationContexts' => ['main'],
                    'parentId' => $parentPage->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        $this->denyAccessToPage($parentPage, $this->anonymousRole);
        $this->grantViewAccessToPage($childPage, $this->anonymousRole);
        $this->entityManager->clear();

        $navResult = $this->navigationRepository->getNavigationFlat(
            'main',
            'en',
            'sulu-test-secure',
            null,
            2,
            $this->getDefaultProperties()
        );

        $navUuids = \array_column($navResult, 'uuid');
        $this->assertNotContains($parentPage->getUuid(), $navUuids, 'Denied parent should be filtered from regular navigation');

        $result = $this->navigationRepository->getBreadcrumb(
            $childPage->getUuid(),
            'en',
            'sulu-test-secure',
            $this->getDefaultProperties()
        );

        $this->assertGreaterThanOrEqual(2, \count($result));
        $uuids = \array_column($result, 'uuid');
        $this->assertContains($parentPage->getUuid(), $uuids);
        $this->assertContains($childPage->getUuid(), $uuids);
    }

    public function testNavigationTreeWithDeniedParentStillShowsAllowedChildren(): void
    {
        $deniedParent = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Parent');

        $childOfDenied = $this->createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Child of Denied',
                    'url' => '/denied-parent/child-of-denied',
                    'navigationContexts' => ['main'],
                    'parentId' => $deniedParent->getUuid(),
                ],
            ],
        ], 'sulu-test-secure');

        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');

        $this->denyAccessToPage($deniedParent, $this->anonymousRole);
        $this->grantViewAccessToPage($childOfDenied, $this->anonymousRole);
        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-test-secure',
            null,
            2,
            $this->getDefaultProperties()
        );

        $uuids = $this->collectTreeUuids($result);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedParent->getUuid(), $uuids);
        $this->assertContains($childOfDenied->getUuid(), $uuids);
    }

    /**
     * @param array<array<string, mixed>> $items
     *
     * @return string[]
     */
    private function collectTreeUuids(array $items): array
    {
        $uuids = [];
        foreach ($items as $item) {
            if (isset($item['uuid']) && \is_string($item['uuid'])) {
                $uuids[] = $item['uuid'];
            }
            if (!empty($item['children']) && \is_array($item['children'])) {
                /** @var array<array<string, mixed>> $children */
                $children = $item['children'];
                $uuids = \array_merge($uuids, $this->collectTreeUuids($children));
            }
        }

        return $uuids;
    }

    private function createSimplePage(string $webspaceKey, string $locale, string $title, string $template = 'default'): Page
    {
        $url = '/' . \strtolower(\str_replace(' ', '-', $title));
        $homepage = 'sulu-io' === $webspaceKey ? $this->homepageNonSecure : $this->homepageSecure;

        return $this->createPage([
            $locale => [
                'live' => [
                    'template' => $template,
                    'title' => $title,
                    'url' => $url,
                    'parentId' => $homepage->getUuid(),
                    'navigationContexts' => ['main'],
                ],
            ],
        ], $webspaceKey);
    }
}
