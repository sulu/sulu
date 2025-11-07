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

class SinglePageSelectionPropertyResolverPermissionTest extends SuluTestCase
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

    public function testPageRepositoryWithoutPermissionConfigReturnsPage(): void
    {
        $page = $this->createSimplePage('sulu-io', 'en', 'Page 1');

        $this->denyAccessToPage($page, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFilters([$page->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(1, $result);
        $this->assertSame($page->getUuid(), $result[0]->getUuid());
    }

    public function testPageRepositoryWithPermissionConfigFiltersPageWithoutPermission(): void
    {
        $deniedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page');

        $this->denyAccessToPage($deniedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFiltersWithPermissions([$deniedPage->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(0, $result);
    }

    public function testPageRepositoryWithPermissionConfigReturnsAllowedPage(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->entityManager->clear();

        $filters = $this->createFiltersWithPermissions([$allowedPage->getUuid()]);

        $result = \iterator_to_array($this->pageRepository->findBy($filters));

        $this->assertCount(1, $result);
        $this->assertSame($allowedPage->getUuid(), $result[0]->getUuid());
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

    public function testPageRepositoryRespectsWebspaceSecurityFlagPerWebspace(): void
    {
        $securePageDenied = $this->createSimplePage('sulu-test-secure', 'en', 'Secure Denied');
        $normalPageDenied = $this->createSimplePage('sulu-io', 'en', 'Normal Denied');

        $this->denyAccessToPage($securePageDenied, $this->anonymousRole);
        $this->denyAccessToPage($normalPageDenied, $this->anonymousRole);
        $this->entityManager->clear();

        $secureFilters = $this->createFiltersWithPermissions([$securePageDenied->getUuid()]);

        $secureResult = \iterator_to_array($this->pageRepository->findBy($secureFilters));

        $this->assertCount(0, $secureResult);

        $normalFilters = $this->createFilters([$normalPageDenied->getUuid()]);

        $normalResult = \iterator_to_array($this->pageRepository->findBy($normalFilters));

        $this->assertCount(1, $normalResult);
        $this->assertSame($normalPageDenied->getUuid(), $normalResult[0]->getUuid());
    }

    public function testPageRepositoryWithEmptySelectionReturnsEmptyArray(): void
    {
        $filters = $this->createFiltersWithPermissions([]);

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
     *     permissionConfig: array{user: null, permission: int},
     * }
     */
    private function createFiltersWithPermissions(array $uuids, int $permission = 64, string $locale = 'en', string $stage = 'live'): array
    {
        return [
            'uuids' => $uuids,
            'locale' => $locale,
            'stage' => $stage,
            'permissionConfig' => [
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
