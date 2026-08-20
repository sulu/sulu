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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Messenger\Envelope;

class NavigationRepositoryTest extends SuluTestCase
{
    use CreatePageTrait;

    private NavigationRepositoryInterface $navigationRepository;

    private Page $parent;
    private Page $child1;
    private Page $grandchild1;

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
            'template' => 'object.templateKey',
            'changed' => 'object.changed',
            'changer' => 'object.changer',
            'created' => 'object.created',
            'creator' => 'object.creator',
            'linkProvider' => 'object.linkData[provider]',
        ];
    }

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->navigationRepository = self::getContainer()->get('sulu_page.navigation_repository');

        // Create hierarchical page structure:
        // parent (main)
        //   ├── child1 (main)
        //   │   └── grandchild1 (main)
        //   └── child2 (footer)

        $this->parent = self::createPage([
            'en' => [
                'live' => [
                    'template' => 'default',
                    'title' => 'Parent Page',
                    'url' => '/parent',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $this->child1 = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Child 1',
                    'url' => '/child1',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Child 2',
                    'url' => '/child2',
                    'navigationContexts' => ['footer'],
                ],
            ],
        ]);

        $this->grandchild1 = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->child1->getUuid(),
                    'template' => 'default',
                    'title' => 'Grandchild 1',
                    'url' => '/grandchild1',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        self::getEntityManager()->flush();
    }

    public function testGetNavigationTreeWithEmptyProperties(): void
    {
        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2
        );

        $this->assertCount(1, $result);
        // no content properties are resolved, only the targetType fallback is added
        $this->assertSame('page', $result[0]['targetType']);
        \assert(\is_array($result[0]['children']));
        $this->assertCount(1, $result[0]['children']);
    }

    public function testGetNavigationTreeDoesNotCrashOnFalsyPropertyValues(): void
    {
        foreach (['', '0'] as $falsyValue) {
            $result = $this->navigationRepository->getNavigationTree(
                'main',
                'en',
                'sulu-io',
                null,
                2,
                ['title' => $falsyValue]
            );

            $this->assertCount(1, $result);
            $this->assertArrayNotHasKey('title', $result[0]);
        }
    }

    public function testGetNavigationTreeSkipsUnpublishedPages(): void
    {
        $draftOnly = self::createPage([
            'en' => [
                'draft' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Draft Only Page',
                    'url' => '/draft-only',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $unpublished = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Unpublished Page',
                    'url' => '/unpublished',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $messageBus = self::getContainer()->get('sulu_message_bus');
        $messageBus->dispatch(new Envelope(
            new ApplyWorkflowTransitionPageMessage(
                identifier: ['uuid' => $unpublished->getUuid()],
                locale: 'en',
                transitionName: WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH
            ),
            [new EnableFlushStamp()]
        ));

        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $result);
        $this->assertSame('Parent Page', $result[0]['title']);
        \assert(\is_array($result[0]['children']));
        $childTitles = \array_column($result[0]['children'], 'title');
        $this->assertNotContains('Unpublished Page', $childTitles);
        $this->assertNotContains('Draft Only Page', $childTitles);
    }

    public function testGetNavigationTreeSkipsPagesNotPublishedInTheRequestedLocale(): void
    {
        self::createPage([
            'de' => [
                'live' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Nur Deutsch',
                    'url' => '/nur-deutsch',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $result);
        \assert(\is_array($result[0]['children']));
        $this->assertNotContains('Nur Deutsch', \array_column($result[0]['children'], 'title'));
    }

    public function testGetNavigationFlatByUuid(): void
    {
        $result = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result);
        $this->assertSame('Child 1', $result[0]['title']);
        $this->assertSame('Child 2', $result[1]['title']);
    }

    public function testGetNavigationFlatByUuidWithDepth(): void
    {
        // Depth 1 should return only direct children
        $result1 = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result1);

        // Depth 2 should return children and grandchildren
        $result2 = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(3, $result2);
        $titles = \array_column($result2, 'title');
        $this->assertContains('Child 1', $titles);
        $this->assertContains('Child 2', $titles);
        $this->assertContains('Grandchild 1', $titles);
    }

    public function testGetNavigationFlatByUuidWithNavigationContext(): void
    {
        $result = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            'main',
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $result);
        $this->assertSame('Child 1', $result[0]['title']);

        $resultFooter = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            'footer',
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $resultFooter);
        $this->assertSame('Child 2', $resultFooter[0]['title']);
    }

    public function testGetNavigationFlatByUuidReturnsEmptyForInvalidUuid(): void
    {
        $result = $this->navigationRepository->getNavigationFlatByUuid(
            'non-existent-uuid',
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertSame([], $result);
    }

    public function testGetNavigationTreeByUuid(): void
    {
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result);
        $this->assertSame('Child 1', $result[0]['title']);
        $this->assertArrayHasKey('children', $result[0]);
        $this->assertSame('Child 2', $result[1]['title']);
        $this->assertArrayHasKey('children', $result[1]);
    }

    public function testGetNavigationTreeByUuidWithDepth(): void
    {
        // Depth 1 should return children but no grandchildren
        $result1 = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result1);
        $this->assertEmpty($result1[0]['children']);
        $this->assertEmpty($result1[1]['children']);

        // Depth 2 should include grandchildren in tree
        $result2 = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2,
            null,
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result2);
        \assert(\is_array($result2[0]['children']));
        $this->assertCount(1, $result2[0]['children']);
        \assert(\is_array($result2[0]['children'][0]));
        $this->assertSame('Grandchild 1', $result2[0]['children'][0]['title']);
    }

    public function testGetNavigationTreeByUuidWithNavigationContext(): void
    {
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2,
            'main',
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $result);
        $this->assertSame('Child 1', $result[0]['title']);
        \assert(\is_array($result[0]['children']));
        $this->assertCount(1, $result[0]['children']);
        \assert(\is_array($result[0]['children'][0]));
        $this->assertSame('Grandchild 1', $result[0]['children'][0]['title']);
    }

    public function testGetNavigationTreeDoesNotPromoteChildrenOfFilteredParentsToRootLevel(): void
    {
        $hiddenParent = self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $this->parent->getUuid(),
                    'template' => 'default',
                    'title' => 'Hidden Parent',
                    'url' => '/hidden-parent',
                    'navigationContexts' => ['footer'],
                ],
            ],
        ]);

        self::createPage([
            'en' => [
                'live' => [
                    'parentId' => $hiddenParent->getUuid(),
                    'template' => 'default',
                    'title' => 'Promoted Grandchild',
                    'url' => '/promoted-grandchild',
                    'navigationContexts' => ['main'],
                ],
            ],
        ]);

        $result = $this->navigationRepository->getNavigationTree(
            'main',
            'en',
            'sulu-io',
            null,
            2,
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $result);
        $this->assertSame('Parent Page', $result[0]['title']);
        \assert(\is_array($result[0]['children']));
        $this->assertSame(['Child 1'], \array_column($result[0]['children'], 'title'));
    }

    public function testGetNavigationTreeByUuidReturnsEmptyForInvalidUuid(): void
    {
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            'non-existent-uuid',
            'en',
            'sulu-io',
            1,
            null,
            $this->getDefaultProperties()
        );

        $this->assertSame([], $result);
    }

    public function testGetBreadcrumb(): void
    {
        $result = $this->navigationRepository->getBreadcrumb(
            $this->grandchild1->getUuid(),
            'en',
            'sulu-io',
            $this->getDefaultProperties()
        );

        $this->assertCount(3, $result);

        $this->assertSame('Parent Page', $result[0]['title']);
        $this->assertSame('Child 1', $result[1]['title']);
        $this->assertSame('Grandchild 1', $result[2]['title']);
    }

    public function testGetBreadcrumbOrder(): void
    {
        $result = $this->navigationRepository->getBreadcrumb(
            $this->child1->getUuid(),
            'en',
            'sulu-io',
            $this->getDefaultProperties()
        );

        $this->assertCount(2, $result);
        $this->assertSame('Parent Page', $result[0]['title']);
        $this->assertSame('Child 1', $result[1]['title']);

        $resultParent = $this->navigationRepository->getBreadcrumb(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            $this->getDefaultProperties()
        );

        $this->assertCount(1, $resultParent);
        $this->assertSame('Parent Page', $resultParent[0]['title']);
    }

    public function testGetBreadcrumbReturnsEmptyForInvalidUuid(): void
    {
        $result = $this->navigationRepository->getBreadcrumb(
            'non-existent-uuid',
            'en',
            'sulu-io',
            $this->getDefaultProperties()
        );

        $this->assertSame([], $result);
    }
}
