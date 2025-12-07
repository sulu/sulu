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

namespace Sulu\Page\Tests\Unit\Infrastructure\Doctrine\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentResolver\ContentResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Doctrine\Repository\NavigationRepository;

class NavigationRepositoryTest extends TestCase
{
    use ProphecyTrait;

    private NavigationRepository $navigationRepository;
    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;
    /** @var ObjectProphecy<NestedTreeRepository<object>> */
    private ObjectProphecy $nestedTreeRepository;
    /** @var ObjectProphecy<object> */
    private ObjectProphecy $dimensionContentRepository;
    /** @var ObjectProphecy<DimensionContentQueryEnhancer> */
    private ObjectProphecy $dimensionContentQueryEnhancer;
    /** @var ObjectProphecy<ContentAggregatorInterface> */
    private ObjectProphecy $contentAggregator;
    /** @var ObjectProphecy<ContentResolverInterface> */
    private ObjectProphecy $contentResolver;

    /**
     * @return array<string, string>
     */
    private function getDefaultProperties(bool $loadExcerpt): array
    {
        $properties = [
            'uuid' => 'object.resource.id',
            'title' => 'title',
            'url' => 'url',
            'webspaceKey' => 'object.resource.webspaceKey',
            'template' => 'object.templateKey',
            'changed' => 'object.changed',
            'changer' => 'object.changer',
            'created' => 'object.created',
            'creator' => 'object.creator',
            'linkProvider' => 'object.linkData.linkProvider',
        ];

        if ($loadExcerpt) {
            $properties['excerpt.title'] = 'excerpt.title';
            $properties['excerpt.more'] = 'excerpt.more';
            $properties['excerpt.description'] = 'excerpt.description';
            $properties['excerpt.segment'] = 'excerpt.segment';
            $properties['excerpt.categories'] = 'excerpt.categories';
            $properties['excerpt.tags'] = 'excerpt.tags';
            $properties['excerpt.audienceTargetGroups'] = 'excerpt.audienceTargetGroups';
            $properties['excerpt.icon'] = 'excerpt.icon';
            $properties['excerpt.image'] = 'excerpt.image';
        }

        return $properties;
    }

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        /** @var ObjectProphecy<NestedTreeRepository<object>> $nestedTreeRepository */
        $nestedTreeRepository = $this->prophesize(NestedTreeRepository::class);
        $this->nestedTreeRepository = $nestedTreeRepository;
        $this->dimensionContentRepository = $this->prophesize('Doctrine\ORM\EntityRepository');
        $this->dimensionContentQueryEnhancer = $this->prophesize(DimensionContentQueryEnhancer::class);
        $this->contentAggregator = $this->prophesize(ContentAggregatorInterface::class);
        $this->contentResolver = $this->prophesize(ContentResolverInterface::class);

        // Setup entity manager to return our mocked repositories
        $this->entityManager->getRepository(PageInterface::class)->willReturn($this->nestedTreeRepository->reveal());
        $this->entityManager->getRepository(PageDimensionContentInterface::class)->willReturn($this->dimensionContentRepository->reveal());

        // Setup repository class names
        $this->nestedTreeRepository->getClassName()->willReturn('Sulu\Page\Domain\Model\Page');
        $this->dimensionContentRepository->getClassName()->willReturn('Sulu\Page\Domain\Model\PageDimensionContent');

        $this->navigationRepository = new NavigationRepository(
            $this->entityManager->reveal(),
            $this->dimensionContentQueryEnhancer->reveal(),
            $this->contentAggregator->reveal(),
            $this->contentResolver->reveal()
        );
    }

    public function testGetNavigationTree(): void
    {
        $page = $this->prophesize(PageInterface::class);
        $page->getChildren()->willReturn(new ArrayCollection([]));
        $page->getWebspaceKey()->willReturn('sulu-io');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(Query::class);

        $this->nestedTreeRepository->createQueryBuilder('page')->willReturn($queryBuilder->reveal());

        // Setup query builder chain
        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('webspaceKey', 'sulu-io')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('page.depth <= :depth')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('depth', 1)->willReturn($queryBuilder->reveal());
        $queryBuilder->addOrderBy('page.lft', 'asc')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());

        // Setup query enhancer
        $expectedFilters = [
            'locale' => 'en',
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'sulu-io',
            'segmentKey' => 'segment-key',
            'stage' => 'live',
        ];
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder->reveal(),
            'page',
            'Sulu\Page\Domain\Model\PageDimensionContent',
            $expectedFilters,
            []
        )->shouldBeCalled();

        // Setup navigation context join
        $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('navigationContext.navigationContext IN (:navigationContexts)')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('navigationContexts', ['main'])->willReturn($queryBuilder->reveal());

        $query->setHint('doctrine.includeMetaColumns', true)->shouldBeCalled();
        $query->getResult('sulu_page_tree')->willReturn([$page->reveal()]);

        // Setup content resolution
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($page->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($dimensionContent->reveal());
        $this->contentResolver->resolve($dimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $page->reveal(),
            'nav' => [
                'title' => 'Page Title',
                'webspaceKey' => 'sulu-io',
                'excerpt' => ['title' => 'Excerpt Title'],
            ],
            'view' => [],
        ]);

        $result = $this->navigationRepository->getNavigationTree('main', 'en', 'sulu-io', 'segment-key', 1, $this->getDefaultProperties(true));

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayHasKey('children', $result[0]);
        $this->assertArrayHasKey('excerpt', $result[0]);
        $this->assertSame('Page Title', $result[0]['title']);
        $this->assertSame('sulu-io', $result[0]['webspaceKey']);
    }

    public function testGetNavigationFlat(): void
    {
        $page = $this->prophesize(PageInterface::class);
        $page->getWebspaceKey()->willReturn('sulu-io');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(Query::class);

        $this->nestedTreeRepository->createQueryBuilder('page')->willReturn($queryBuilder->reveal());

        // Setup query builder chain
        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('webspaceKey', 'sulu-io')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('page.depth <= :depth')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('depth', 2)->willReturn($queryBuilder->reveal());
        $queryBuilder->addOrderBy('page.lft', 'asc')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());

        // Setup query enhancer
        $expectedFilters = [
            'locale' => 'de',
            'navigationContexts' => ['footer'],
            'depth' => 2,
            'webspaceKey' => 'sulu-io',
            'segmentKey' => null,
            'stage' => 'live',
        ];
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder->reveal(),
            'page',
            'Sulu\Page\Domain\Model\PageDimensionContent',
            $expectedFilters,
            []
        )->shouldBeCalled();

        // Setup navigation context join
        $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('navigationContext.navigationContext IN (:navigationContexts)')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('navigationContexts', ['footer'])->willReturn($queryBuilder->reveal());

        $query->toIterable()->willReturn([$page->reveal()]);
        $query->getResult()->willReturn([$page->reveal()]);

        // Setup content resolution
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($page->reveal(), ['locale' => 'de', 'stage' => 'live'])
            ->willReturn($dimensionContent->reveal());
        $this->contentResolver->resolve($dimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $page->reveal(),
            'nav' => [
                'title' => 'German Page',
                'description' => 'Test',
                'webspaceKey' => 'sulu-io',
            ],
            'view' => [],
        ]);

        $result = $this->navigationRepository->getNavigationFlat('footer', 'de', 'sulu-io', null, 2, $this->getDefaultProperties(false));

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('title', $result[0]);
        $this->assertArrayNotHasKey('children', $result[0]); // Flat navigation shouldn't have children
        $this->assertArrayNotHasKey('excerpt', $result[0]); // Excerpt not requested
        $this->assertSame('German Page', $result[0]['title']);
        $this->assertSame('sulu-io', $result[0]['webspaceKey']);
    }

    public function testGetNavigationTreeWithoutSegment(): void
    {
        $page = $this->prophesize(PageInterface::class);
        $page->getChildren()->willReturn(new ArrayCollection([]));
        $page->getWebspaceKey()->willReturn('test-webspace');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(Query::class);

        $this->nestedTreeRepository->createQueryBuilder('page')->willReturn($queryBuilder->reveal());

        // Setup query builder chain
        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('webspaceKey', 'test-webspace')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('page.depth <= :depth')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('depth', 3)->willReturn($queryBuilder->reveal());
        $queryBuilder->addOrderBy('page.lft', 'asc')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());

        // Setup query enhancer with null segmentKey
        $expectedFilters = [
            'locale' => 'en',
            'navigationContexts' => ['sidebar'],
            'depth' => 3,
            'webspaceKey' => 'test-webspace',
            'segmentKey' => null,
            'stage' => 'live',
        ];
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder->reveal(),
            'page',
            'Sulu\Page\Domain\Model\PageDimensionContent',
            $expectedFilters,
            []
        )->shouldBeCalled();

        // Setup navigation context join
        $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('navigationContext.navigationContext IN (:navigationContexts)')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('navigationContexts', ['sidebar'])->willReturn($queryBuilder->reveal());

        $query->setHint('doctrine.includeMetaColumns', true)->shouldBeCalled();
        $query->getResult('sulu_page_tree')->willReturn([$page->reveal()]);

        // Setup content resolution
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($page->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($dimensionContent->reveal());
        $this->contentResolver->resolve($dimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $page->reveal(),
            'nav' => [
                'title' => 'No Segment Page',
                'webspaceKey' => 'test-webspace',
            ],
            'view' => [],
        ]);

        $result = $this->navigationRepository->getNavigationTree('sidebar', 'en', 'test-webspace', null, 3, $this->getDefaultProperties(false));

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertSame('No Segment Page', $result[0]['title']);
        $this->assertSame('test-webspace', $result[0]['webspaceKey']);
    }

    public function testGetNavigationTreeWithMultipleDepthLevels(): void
    {
        $parentPage = $this->prophesize(PageInterface::class);
        $childPage = $this->prophesize(PageInterface::class);

        $parentPage->getChildren()->willReturn(new ArrayCollection([$childPage->reveal()]));
        $parentPage->getWebspaceKey()->willReturn('sulu-io');
        $childPage->getChildren()->willReturn(new ArrayCollection([]));
        $childPage->getWebspaceKey()->willReturn('sulu-io');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(Query::class);

        $this->nestedTreeRepository->createQueryBuilder('page')->willReturn($queryBuilder->reveal());

        // Setup query builder chain for depth 2
        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('webspaceKey', 'sulu-io')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('page.depth <= :depth')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('depth', 2)->willReturn($queryBuilder->reveal());
        $queryBuilder->addOrderBy('page.lft', 'asc')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());

        // Setup query enhancer
        $expectedFilters = [
            'locale' => 'en',
            'navigationContexts' => ['main'],
            'depth' => 2,
            'webspaceKey' => 'sulu-io',
            'segmentKey' => 'segment-a',
            'stage' => 'live',
        ];
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder->reveal(),
            'page',
            'Sulu\Page\Domain\Model\PageDimensionContent',
            $expectedFilters,
            []
        )->shouldBeCalled();

        // Setup navigation context join
        $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('navigationContext.navigationContext IN (:navigationContexts)')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('navigationContexts', ['main'])->willReturn($queryBuilder->reveal());

        $query->setHint('doctrine.includeMetaColumns', true)->shouldBeCalled();
        $query->getResult('sulu_page_tree')->willReturn([$parentPage->reveal()]);

        // Setup content resolution for parent
        $parentDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($parentPage->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($parentDimensionContent->reveal());
        $this->contentResolver->resolve($parentDimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $parentPage->reveal(),
            'nav' => ['title' => 'Parent Page'],
            'view' => [],
        ]);

        // Setup content resolution for child
        $childDimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($childPage->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($childDimensionContent->reveal());
        $this->contentResolver->resolve($childDimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $childPage->reveal(),
            'nav' => ['title' => 'Child Page'],
            'view' => [],
        ]);

        /** @var array<int, array{title: string, children: array<int, array{title: string, children: array<string, mixed>}>}> $result */
        $result = $this->navigationRepository->getNavigationTree('main', 'en', 'sulu-io', 'segment-a', 2, $this->getDefaultProperties(false));

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertSame('Parent Page', $result[0]['title']);
        $this->assertArrayHasKey('children', $result[0]);
        $this->assertCount(1, $result[0]['children']);
        $this->assertSame('Child Page', $result[0]['children'][0]['title']);
        $this->assertSame([], $result[0]['children'][0]['children']); // Child has no children
    }

    public function testGetNavigationFlatWithExcerpt(): void
    {
        $page = $this->prophesize(PageInterface::class);
        $page->getWebspaceKey()->willReturn('sulu-io');

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $query = $this->prophesize(Query::class);

        $this->nestedTreeRepository->createQueryBuilder('page')->willReturn($queryBuilder->reveal());

        // Setup basic query builder chain
        $queryBuilder->andWhere('page.webspaceKey = :webspaceKey')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('webspaceKey', 'sulu-io')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('page.depth <= :depth')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('depth', 1)->willReturn($queryBuilder->reveal());
        $queryBuilder->addOrderBy('page.lft', 'asc')->willReturn($queryBuilder->reveal());
        $queryBuilder->getQuery()->willReturn($query->reveal());

        // Setup query enhancer
        $expectedFilters = [
            'locale' => 'en',
            'navigationContexts' => ['main'],
            'depth' => 1,
            'webspaceKey' => 'sulu-io',
            'segmentKey' => 'test-segment',
            'stage' => 'live',
        ];
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder->reveal(),
            'page',
            'Sulu\Page\Domain\Model\PageDimensionContent',
            $expectedFilters,
            []
        )->shouldBeCalled();

        // Setup navigation context join
        $queryBuilder->leftJoin('filterDimensionContent.navigationContexts', 'navigationContext')->willReturn($queryBuilder->reveal());
        $queryBuilder->andWhere('navigationContext.navigationContext IN (:navigationContexts)')->willReturn($queryBuilder->reveal());
        $queryBuilder->setParameter('navigationContexts', ['main'])->willReturn($queryBuilder->reveal());

        $query->toIterable()->willReturn([$page->reveal()]);
        $query->getResult()->willReturn([$page->reveal()]);

        // Setup content resolution with excerpt
        $dimensionContent = $this->prophesize(DimensionContentInterface::class);
        $this->contentAggregator->aggregate($page->reveal(), ['locale' => 'en', 'stage' => 'live'])
            ->willReturn($dimensionContent->reveal());
        $this->contentResolver->resolve($dimensionContent->reveal(), Argument::type('array'))->willReturn([
            'resource' => $page->reveal(),
            'nav' => [
                'title' => 'Page with Excerpt',
                'excerpt' => [
                    'title' => 'Excerpt Title',
                    'description' => 'Excerpt Description',
                    'segment' => 'test-segment',
                    'audienceTargetGroups' => [1, 2],
                ],
            ],
            'view' => [],
        ]);

        /** @var array<int, array{excerpt?: array{title: string, segment: string}}> $result */
        $result = $this->navigationRepository->getNavigationFlat('main', 'en', 'sulu-io', 'test-segment', 1, $this->getDefaultProperties(true));

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('excerpt', $result[0]);
        $this->assertSame('Excerpt Title', $result[0]['excerpt']['title']);
        $this->assertSame('test-segment', $result[0]['excerpt']['segment']);
    }
}
