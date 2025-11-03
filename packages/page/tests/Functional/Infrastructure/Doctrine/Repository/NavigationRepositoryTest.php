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
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Repository\NavigationRepositoryInterface;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

class NavigationRepositoryTest extends SuluTestCase
{
    use CreatePageTrait;

    private NavigationRepositoryInterface $navigationRepository;

    private Page $parent;
    private Page $child1;
    private Page $child2;
    private Page $grandchild1;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->navigationRepository = self::getContainer()->get('sulu_page.navigation_repository');

        $messageBus = self::getContainer()->get('sulu_message_bus');

        // Create hierarchical page structure:
        // parent (main)
        //   ├── child1 (main)
        //   │   └── grandchild1 (main)
        //   └── child2 (footer)

        // Create parent page
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreatePageMessage(
                    webspaceKey: 'sulu-io',
                    parentId: CreatePageMessageHandler::HOMEPAGE_PARENT_ID,
                    data: [
                        'locale' => 'en',
                        'template' => 'default',
                        'title' => 'Parent Page',
                        'url' => '/parent',
                        'navigationContexts' => ['main'],
                    ]
                ),
                [new EnableFlushStamp()]
            )
        );
        $result = $envelope->all(HandledStamp::class)[0]->getResult();
        \assert($result instanceof Page);
        $this->parent = $result;

        // Publish parent
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $this->parent->getUuid()],
                    locale: 'en',
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                ),
                [new EnableFlushStamp()]
            )
        );

        // Create child1 under parent
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreatePageMessage(
                    webspaceKey: 'sulu-io',
                    parentId: $this->parent->getUuid(),
                    data: [
                        'locale' => 'en',
                        'template' => 'default',
                        'title' => 'Child 1',
                        'url' => '/child1',
                        'navigationContexts' => ['main'],
                    ]
                ),
                [new EnableFlushStamp()]
            )
        );
        $result = $envelope->all(HandledStamp::class)[0]->getResult();
        \assert($result instanceof Page);
        $this->child1 = $result;

        // Publish child1
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $this->child1->getUuid()],
                    locale: 'en',
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                ),
                [new EnableFlushStamp()]
            )
        );

        // Create child2 under parent
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreatePageMessage(
                    webspaceKey: 'sulu-io',
                    parentId: $this->parent->getUuid(),
                    data: [
                        'locale' => 'en',
                        'template' => 'default',
                        'title' => 'Child 2',
                        'url' => '/child2',
                        'navigationContexts' => ['footer'],
                    ]
                ),
                [new EnableFlushStamp()]
            )
        );
        $result = $envelope->all(HandledStamp::class)[0]->getResult();
        \assert($result instanceof Page);
        $this->child2 = $result;

        // Publish child2
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $this->child2->getUuid()],
                    locale: 'en',
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                ),
                [new EnableFlushStamp()]
            )
        );

        // Create grandchild1 under child1
        $envelope = $messageBus->dispatch(
            new Envelope(
                new CreatePageMessage(
                    webspaceKey: 'sulu-io',
                    parentId: $this->child1->getUuid(),
                    data: [
                        'locale' => 'en',
                        'template' => 'default',
                        'title' => 'Grandchild 1',
                        'url' => '/grandchild1',
                        'navigationContexts' => ['main'],
                    ]
                ),
                [new EnableFlushStamp()]
            )
        );
        $result = $envelope->all(HandledStamp::class)[0]->getResult();
        \assert($result instanceof Page);
        $this->grandchild1 = $result;

        // Publish grandchild1
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $this->grandchild1->getUuid()],
                    locale: 'en',
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH
                ),
                [new EnableFlushStamp()]
            )
        );

        self::getEntityManager()->flush();
    }

    public function testGetNavigationFlatByUuid(): void
    {
        $result = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1
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
            1
        );

        $this->assertCount(2, $result1);

        // Depth 2 should return children and grandchildren
        $result2 = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2
        );

        $this->assertCount(3, $result2);
        $titles = \array_column($result2, 'title');
        $this->assertContains('Child 1', $titles);
        $this->assertContains('Child 2', $titles);
        $this->assertContains('Grandchild 1', $titles);
    }

    public function testGetNavigationFlatByUuidWithNavigationContext(): void
    {
        // Filter by 'main' context
        $result = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            'main'
        );

        $this->assertCount(1, $result);
        $this->assertSame('Child 1', $result[0]['title']);

        // Filter by 'footer' context
        $resultFooter = $this->navigationRepository->getNavigationFlatByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1,
            'footer'
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
            1
        );

        $this->assertSame([], $result);
    }

    public function testGetNavigationTreeByUuid(): void
    {
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            1
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
            1
        );

        $this->assertCount(2, $result1);
        $this->assertEmpty($result1[0]['children']);
        $this->assertEmpty($result1[1]['children']);

        // Depth 2 should include grandchildren in tree
        $result2 = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2
        );

        $this->assertCount(2, $result2);
        \assert(\is_array($result2[0]['children']));
        $this->assertCount(1, $result2[0]['children']);
        \assert(\is_array($result2[0]['children'][0]));
        $this->assertSame('Grandchild 1', $result2[0]['children'][0]['title']);
    }

    public function testGetNavigationTreeByUuidWithNavigationContext(): void
    {
        // Filter by 'main' context
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            $this->parent->getUuid(),
            'en',
            'sulu-io',
            2,
            'main'
        );

        $this->assertCount(1, $result);
        $this->assertSame('Child 1', $result[0]['title']);
        \assert(\is_array($result[0]['children']));
        $this->assertCount(1, $result[0]['children']);
        \assert(\is_array($result[0]['children'][0]));
        $this->assertSame('Grandchild 1', $result[0]['children'][0]['title']);
    }

    public function testGetNavigationTreeByUuidReturnsEmptyForInvalidUuid(): void
    {
        $result = $this->navigationRepository->getNavigationTreeByUuid(
            'non-existent-uuid',
            'en',
            'sulu-io',
            1
        );

        $this->assertSame([], $result);
    }

    public function testGetBreadcrumb(): void
    {
        $result = $this->navigationRepository->getBreadcrumb(
            $this->grandchild1->getUuid(),
            'en',
            'sulu-io'
        );

        $this->assertCount(3, $result);

        // Verify root -> current order
        $this->assertSame('Parent Page', $result[0]['title']);
        $this->assertSame('Child 1', $result[1]['title']);
        $this->assertSame('Grandchild 1', $result[2]['title']);
    }

    public function testGetBreadcrumbOrder(): void
    {
        // Test with child1 - should have 2 items
        $result = $this->navigationRepository->getBreadcrumb(
            $this->child1->getUuid(),
            'en',
            'sulu-io'
        );

        $this->assertCount(2, $result);
        $this->assertSame('Parent Page', $result[0]['title']);
        $this->assertSame('Child 1', $result[1]['title']);

        // Test with parent - should have 1 item
        $resultParent = $this->navigationRepository->getBreadcrumb(
            $this->parent->getUuid(),
            'en',
            'sulu-io'
        );

        $this->assertCount(1, $resultParent);
        $this->assertSame('Parent Page', $resultParent[0]['title']);
    }

    public function testGetBreadcrumbReturnsEmptyForInvalidUuid(): void
    {
        $result = $this->navigationRepository->getBreadcrumb(
            'non-existent-uuid',
            'en',
            'sulu-io'
        );

        $this->assertSame([], $result);
    }
}
