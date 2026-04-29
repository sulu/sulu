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

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page as SuluPage;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;
use Sulu\Page\Tests\Application\Entity\Page as CustomPage;
use Sulu\Page\Tests\Traits\CreatePageTrait;

/**
 * The test boots a second kernel (`test_custom_page_model`) in addition to the default
 * one. Sulu's `adminWebspaceCollectionCache` uses a hardcoded class name and would clash
 * in the same PHP process, so the test runs in isolation.
 */
#[RunTestsInSeparateProcesses]
class CustomPageModelTreeTest extends SuluTestCase
{
    use CreatePageTrait;

    private EntityManagerInterface $entityManager;
    private NavigationRepositoryInterface $navigationRepository;
    private PageRepositoryInterface $pageRepository;

    /**
     * @return array<string, string>
     */
    protected static function getKernelConfiguration(): array
    {
        return ['environment' => 'test_custom_page_model'];
    }

    protected function setUp(): void
    {
        self::bootKernel();
        self::purgeDatabase();

        $this->entityManager = self::getEntityManager();
        $this->navigationRepository = self::getContainer()->get('sulu_page.navigation_repository');
        $this->pageRepository = self::getContainer()->get('sulu_page.page_repository');

        $this->ensureCustomTextColumn();
    }

    private function ensureCustomTextColumn(): void
    {
        $connection = $this->entityManager->getConnection();
        $columns = $connection->createSchemaManager()->listTableColumns('pa_pages');

        if (isset($columns['custom_text'])) {
            return;
        }

        $connection->executeStatement('ALTER TABLE pa_pages ADD COLUMN custom_text VARCHAR(255) DEFAULT NULL');
    }

    public function testCustomConcretePageModelSupportsTreeOperationsAndTreeHydration(): void
    {
        self::assertSame(CustomPage::class, self::getContainer()->getParameter('sulu.model.page.class'));

        $parent = self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Parent Page',
                    'url' => '/parent',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $child1 = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Child 1',
                    'url' => '/parent/child-1',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $child2 = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Child 2',
                    'url' => '/parent/child-2',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $grandchild = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $child1->getUuid(),
                    'template' => 'default',
                    'title' => 'Grandchild 1',
                    'url' => '/parent/child-1/grandchild-1',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $this->entityManager->clear();

        $reloadedParent = $this->entityManager->find(CustomPage::class, $parent->getUuid());
        $reloadedChild1 = $this->entityManager->find(CustomPage::class, $child1->getUuid());
        $reloadedChild2 = $this->entityManager->find(CustomPage::class, $child2->getUuid());
        $reloadedGrandchild = $this->entityManager->find(CustomPage::class, $grandchild->getUuid());

        self::assertInstanceOf(CustomPage::class, $reloadedParent);
        self::assertInstanceOf(CustomPage::class, $reloadedChild1);
        self::assertInstanceOf(CustomPage::class, $reloadedChild2);
        self::assertInstanceOf(CustomPage::class, $reloadedGrandchild);

        self::assertSame($reloadedParent->getUuid(), $reloadedChild1->getParent()?->getUuid());
        self::assertSame($reloadedParent->getUuid(), $reloadedChild2->getParent()?->getUuid());
        self::assertSame($reloadedChild1->getUuid(), $reloadedGrandchild->getParent()?->getUuid());
        self::assertLessThan($reloadedChild2->getLft(), $reloadedChild1->getLft());
        self::assertGreaterThan($reloadedChild1->getLft(), $reloadedGrandchild->getLft());
        self::assertLessThan($reloadedChild1->getRgt(), $reloadedGrandchild->getRgt());

        $navigation = $this->navigationRepository->getNavigationTreeByUuid(
            $parent->getUuid(),
            'en',
            'sulu-io',
            2,
            'main',
            $this->getDefaultProperties()
        );

        self::assertCount(2, $navigation);
        self::assertSame('Child 1', $navigation[0]['title']);
        self::assertSame('Child 2', $navigation[1]['title']);

        self::assertIsArray($navigation[0]['children']);
        self::assertCount(1, $navigation[0]['children']);
        $grandchildNav = $navigation[0]['children'][0];
        self::assertIsArray($grandchildNav);
        self::assertSame('Grandchild 1', $grandchildNav['title']);
        self::assertGreaterThan($navigation[0]['lft'], $grandchildNav['lft']);
        self::assertLessThan($navigation[0]['rgt'], $grandchildNav['rgt']);

        $reloadedParent->setCustomText('persisted-custom-value');
        $this->entityManager->flush();
        $this->entityManager->clear();

        $withCustomField = $this->entityManager->find(CustomPage::class, $parent->getUuid());
        self::assertInstanceOf(CustomPage::class, $withCustomField);
        self::assertSame('persisted-custom-value', $withCustomField->getCustomText());

        $metadata = $this->entityManager->getClassMetadata(CustomPage::class);
        self::assertSame(CustomPage::class, self::mappingDeclared($metadata->fieldMappings['customText']) ?? CustomPage::class);
        self::assertSame(SuluPage::class, self::mappingDeclared($metadata->associationMappings['parent']));
        self::assertSame(SuluPage::class, self::mappingDeclared($metadata->associationMappings['children']));

        // Exercises Gedmo's `sulu_page_tree` hydrator (the code path guarded by SafeTreeObjectHydrator).
        $treeRoots = \iterator_to_array($this->pageRepository->findByAsTree([
            'webspaceKey' => 'sulu-io',
            'locale' => 'en',
        ]));
        self::assertNotEmpty($treeRoots);
    }

    /**
     * Reads the `declared` metadata key from a mapping, supporting both
     * ORM 3.x (object mappings) and ORM 2.x (array mappings).
     */
    private static function mappingDeclared(mixed $mapping): ?string
    {
        if (\is_array($mapping)) {
            $value = $mapping['declared'] ?? null;
        } else {
            $value = \is_object($mapping) && \property_exists($mapping, 'declared') ? $mapping->declared : null;
        }

        return \is_string($value) ? $value : null;
    }

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
            'depth' => 'object.resource.depth',
            'lft' => 'object.resource.lft',
            'rgt' => 'object.resource.rgt',
        ];
    }
}
