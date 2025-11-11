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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Content\PropertyResolver;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class PageSelectionPropertyResolverPermissionTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    private EntityManagerInterface $entityManager;
    private PageRepositoryInterface $pageRepository;
    private Role $anonymousRole;
    private Page $homepageNonSecure;
    private Page $homepageSecure;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();
        $this->pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions('sulu-test-secure');

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

    public function testPageRepositoryWithoutaccessControlReturnsAllPages(): void
    {
        $page1 = $this->createSimplePage('sulu-io', 'en', 'Page 1');
        $page2 = $this->createSimplePage('sulu-io', 'en', 'Page 2');
        $page3 = $this->createSimplePage('sulu-io', 'en', 'Page 3');

        $this->denyAccessToPage($page2, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFilters([$page1->getUuid(), $page2->getUuid(), $page3->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(3, $result);
    }

    public function testPageRepositoryWithaccessControlFiltersPagesWithoutPermissions(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 1');
        $deniedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 2');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage1, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage2, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFiltersWithPermissions([$allowedPage->getUuid(), $deniedPage1->getUuid(), $deniedPage2->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(1, $result);
        $uuids = \array_map(fn ($page) => $page->getUuid(), $result);
        $this->assertContains($allowedPage->getUuid(), $uuids);
        $this->assertNotContains($deniedPage1->getUuid(), $uuids);
        $this->assertNotContains($deniedPage2->getUuid(), $uuids);
    }

    public function testPageRepositoryWithMixedPermissions(): void
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

        $filters = $this->createFiltersWithPermissions([
            $allowed1->getUuid(),
            $allowed2->getUuid(),
            $denied1->getUuid(),
            $denied2->getUuid(),
        ]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(2, $result);
        $uuids = \array_map(fn ($page) => $page->getUuid(), $result);
        $this->assertContains($allowed1->getUuid(), $uuids);
        $this->assertContains($allowed2->getUuid(), $uuids);
        $this->assertNotContains($denied1->getUuid(), $uuids);
        $this->assertNotContains($denied2->getUuid(), $uuids);
    }

    public function testPageRepositoryWithPageWithoutAccessControlEntryUsesWebspacePermissions(): void
    {
        $pageWithoutAcl = $this->createSimplePage('sulu-test-secure', 'en', 'Page Without ACL');
        $this->entityManager->clear();

        $filters = $this->createFiltersWithPermissions([$pageWithoutAcl->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(1, $result);
        $this->assertSame($pageWithoutAcl->getUuid(), $result[0]->getUuid());
    }

    public function testPageRepositoryWithEmptySelection(): void
    {
        $filters = $this->createFiltersWithPermissions([]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(0, $result);
    }

    public function testPageRepositoryWithAllDeniedPages(): void
    {
        $denied1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 1');
        $denied2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 2');

        $this->denyAccessToPage($denied1, $this->anonymousRole);
        $this->denyAccessToPage($denied2, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFiltersWithPermissions([$denied1->getUuid(), $denied2->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(0, $result);
    }

    /**
     * @param string[] $uuids
     *
     * @return array{
     *     uuids: string[],
     *     locale: string,
     *     stage: string,
     * }
     */
    private function createFilters(array $uuids, string $locale = 'en', string $stage = 'live'): array
    {
        return [
            'uuids' => $uuids,
            'locale' => $locale,
            'stage' => $stage,
        ];
    }

    /**
     * @param string[] $uuids
     *
     * @return array{
     *     uuids: string[],
     *     locale: string,
     *     stage: string,
     *     accessControl: array{user: null, permission: int},
     * }
     */
    private function createFiltersWithPermissions(array $uuids, int $permission = 64, string $locale = 'en', string $stage = 'live'): array
    {
        return [
            'uuids' => $uuids,
            'locale' => $locale,
            'stage' => $stage,
            'accessControl' => [
                'user' => null,
                'permission' => $permission,
            ],
        ];
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
                ],
            ],
        ], $webspaceKey);
    }
}
