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
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Page\Tests\Traits\CreatePageWithPermissionsTrait;

class PageRepositoryTest extends SuluTestCase
{
    use CreatePageTrait;
    use CreatePageWithPermissionsTrait;

    private EntityManagerInterface $entityManager;
    private PageRepositoryInterface $pageRepository;
    private Role $anonymousRole;
    private Page $homepageNonSecure;
    private Page $homepageSecure;
    private int $viewPermission;

    protected function setUp(): void
    {
        $this->purgeDatabase();
        $this->entityManager = $this->getEntityManager();

        $this->pageRepository = $this->getContainer()->get('sulu_page.page_repository');
        $this->anonymousRole = $this->createAnonymousRoleWithWebspacePermissions('sulu-test-secure');

        $permissions = $this->getContainer()->getParameter('sulu_security.permissions');
        $this->viewPermission = $permissions[PermissionTypes::VIEW];

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

    public function testFindIdentifiersByWithoutPermissionsReturnsAllPages(): void
    {
        // Create pages
        $page1 = $this->createSimplePage('sulu-io', 'en', 'Page 1');
        $page2 = $this->createSimplePage('sulu-io', 'en', 'Page 2');
        $page3 = $this->createSimplePage('sulu-io', 'en', 'Page 3');

        $this->entityManager->clear();

        $uuids = [$page1->getUuid(), $page2->getUuid(), $page3->getUuid()];
        $result = $this->pageRepository->findIdentifiersBy([
            'uuids' => $uuids,
            'locale' => 'en',
            'stage' => 'live',
        ]);

        $resultUuids = [...$result];

        $this->assertCount(3, $resultUuids);
        $this->assertContains($page1->getUuid(), $resultUuids);
        $this->assertContains($page2->getUuid(), $resultUuids);
        $this->assertContains($page3->getUuid(), $resultUuids);
    }

    public function testFindIdentifiersByWithPermissionsFiltersPages(): void
    {
        $allowedPage = $this->createSimplePage('sulu-test-secure', 'en', 'Allowed Page');
        $deniedPage1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 1');
        $deniedPage2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied Page 2');

        $this->grantViewAccessToPage($allowedPage, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage1, $this->anonymousRole);
        $this->denyAccessToPage($deniedPage2, $this->anonymousRole);

        $this->entityManager->clear();

        $systemStore = $this->getContainer()->get('sulu_security.system_store');
        $systemStore->setSystem('sulu-test-secure');

        $uuids = [$allowedPage->getUuid(), $deniedPage1->getUuid(), $deniedPage2->getUuid()];

        $result = $this->pageRepository->findIdentifiersBy(
            [
                'uuids' => $uuids,
                'locale' => 'en',
                'stage' => 'live',
                'accessControl' => [
                    'user' => null,
                    'permission' => $this->viewPermission,
                ],
            ]
        );

        $resultUuids = [...$result];

        $this->assertContains($allowedPage->getUuid(), $resultUuids);
        $this->assertNotContains($deniedPage1->getUuid(), $resultUuids);
        $this->assertNotContains($deniedPage2->getUuid(), $resultUuids);
    }

    public function testFindIdentifiersByWithMixedPermissions(): void
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

        $systemStore = $this->getContainer()->get('sulu_security.system_store');
        $systemStore->setSystem('sulu-test-secure');

        $uuids = [$allowed1->getUuid(), $denied1->getUuid(), $allowed2->getUuid(), $denied2->getUuid()];

        $result = $this->pageRepository->findIdentifiersBy(
            [
                'uuids' => $uuids,
                'locale' => 'en',
                'stage' => 'live',
                'accessControl' => [
                    'user' => null,
                    'permission' => $this->viewPermission,
                ],
            ]
        );

        $resultUuids = [...$result];

        $this->assertCount(2, $resultUuids);
        $this->assertContains($allowed1->getUuid(), $resultUuids);
        $this->assertContains($allowed2->getUuid(), $resultUuids);
        $this->assertNotContains($denied1->getUuid(), $resultUuids);
        $this->assertNotContains($denied2->getUuid(), $resultUuids);
    }

    public function testFindIdentifiersByWithAllDeniedReturnsEmpty(): void
    {
        $denied1 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 1');
        $denied2 = $this->createSimplePage('sulu-test-secure', 'en', 'Denied 2');

        $this->denyAccessToPage($denied1, $this->anonymousRole);
        $this->denyAccessToPage($denied2, $this->anonymousRole);

        $this->entityManager->clear();

        $systemStore = $this->getContainer()->get('sulu_security.system_store');
        $systemStore->setSystem('sulu-test-secure');

        $uuids = [$denied1->getUuid(), $denied2->getUuid()];

        $result = $this->pageRepository->findIdentifiersBy(
            [
                'uuids' => $uuids,
                'locale' => 'en',
                'stage' => 'live',
                'accessControl' => [
                    'user' => null,
                    'permission' => $this->viewPermission,
                ],
            ]
        );

        $this->assertSame([], \iterator_to_array($result));
    }

    public function testFindIdentifiersByWithoutAccessControlUsesWebspacePermissions(): void
    {
        $pageWithoutAcl = $this->createSimplePage('sulu-test-secure', 'en', 'Page Without ACL');

        $this->entityManager->clear();

        $systemStore = $this->getContainer()->get('sulu_security.system_store');
        $systemStore->setSystem('sulu-test-secure');

        $uuids = [$pageWithoutAcl->getUuid()];

        $result = $this->pageRepository->findIdentifiersBy(
            [
                'uuids' => $uuids,
                'locale' => 'en',
                'stage' => 'live',
                'accessControl' => [
                    'user' => null,
                    'permission' => $this->viewPermission,
                ],
            ]
        );

        $resultUuids = [...$result];

        $this->assertContains($pageWithoutAcl->getUuid(), $resultUuids);
    }

    public function testFindDescendantIdsById(): void
    {
        $parent = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Parent',
                    'url' => '/parent',
                    'template' => 'default',
                ],
            ],
        ]);

        $child1 = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child 1',
                    'url' => '/parent/child1',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $child2 = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child 2',
                    'url' => '/parent/child2',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $grandchild = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Grandchild',
                    'url' => '/parent/child1/grandchild',
                    'template' => 'default',
                    'parentId' => $child1->getUuid(),
                ],
            ],
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $descendantIds = $this->pageRepository->findDescendantIdsById($parent->getUuid());

        $this->assertCount(3, $descendantIds);
        $this->assertContains($child1->getUuid(), $descendantIds);
        $this->assertContains($child2->getUuid(), $descendantIds);
        $this->assertContains($grandchild->getUuid(), $descendantIds);
    }

    public function testFindDescendantIdsByIdWithNoChildren(): void
    {
        $page = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Single Page',
                    'url' => '/single',
                    'template' => 'default',
                ],
            ],
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $descendantIds = $this->pageRepository->findDescendantIdsById($page->getUuid());

        $this->assertEmpty($descendantIds);
    }

    public function testSupportsDescendantType(): void
    {
        $this->assertTrue($this->pageRepository->supportsDescendantType(PageInterface::class));
        $this->assertTrue($this->pageRepository->supportsDescendantType(Page::class));
        $this->assertFalse($this->pageRepository->supportsDescendantType('SomeOtherClass'));
    }

    public function testFindDescendantIdsByIdWithDeepNesting(): void
    {
        $parent = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Parent',
                    'url' => '/parent',
                    'template' => 'default',
                ],
            ],
        ]);

        $child = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child',
                    'url' => '/parent/child',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $grandchild = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Grandchild',
                    'url' => '/parent/child/grandchild',
                    'template' => 'default',
                    'parentId' => $child->getUuid(),
                ],
            ],
        ]);

        $greatGrandchild = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Great Grandchild',
                    'url' => '/parent/child/grandchild/great',
                    'template' => 'default',
                    'parentId' => $grandchild->getUuid(),
                ],
            ],
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $descendantIds = $this->pageRepository->findDescendantIdsById($parent->getUuid());

        $this->assertCount(3, $descendantIds);
        $this->assertContains($child->getUuid(), $descendantIds);
        $this->assertContains($grandchild->getUuid(), $descendantIds);
        $this->assertContains($greatGrandchild->getUuid(), $descendantIds);
    }

    public function testFindDescendantIdsByIdWithMultipleSiblings(): void
    {
        $parent = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Parent',
                    'url' => '/parent',
                    'template' => 'default',
                ],
            ],
        ]);

        $child1 = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child 1',
                    'url' => '/parent/child1',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $child2 = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child 2',
                    'url' => '/parent/child2',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $child3 = self::createPage([
            'en' => [
                'draft' => [
                    'title' => 'Child 3',
                    'url' => '/parent/child3',
                    'template' => 'default',
                    'parentId' => $parent->getUuid(),
                ],
            ],
        ]);

        $this->entityManager->flush();
        $this->entityManager->clear();

        $descendantIds = $this->pageRepository->findDescendantIdsById($parent->getUuid());

        $this->assertCount(3, $descendantIds);
        $this->assertContains($child1->getUuid(), $descendantIds);
        $this->assertContains($child2->getUuid(), $descendantIds);
        $this->assertContains($child3->getUuid(), $descendantIds);
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
