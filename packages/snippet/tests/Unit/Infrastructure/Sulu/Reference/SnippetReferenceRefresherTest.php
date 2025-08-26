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

namespace Sulu\Snippet\Tests\Unit\Infrastructure\Sulu\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ReferenceBundle\Domain\Repository\ReferenceRepositoryInterface;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Content\Application\ContentMerger\ContentMergerInterface;
use Sulu\Content\Application\ContentResolver\ContentViewResolver\ContentViewResolverInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Snippet\Domain\Model\Snippet;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Infrastructure\Sulu\Reference\SnippetReferenceRefresher;

class SnippetReferenceRefresherTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    private SnippetReferenceRefresher $refresher;

    /** @var ObjectProphecy<EntityManagerInterface> */
    private ObjectProphecy $entityManager;

    /** @var ObjectProphecy<ReferenceRepositoryInterface> */
    private ObjectProphecy $referenceRepository;

    /** @var ObjectProphecy<ContentViewResolverInterface> */
    private ObjectProphecy $contentViewResolver;

    /** @var ObjectProphecy<ContentMergerInterface> */
    private ObjectProphecy $contentMerger;

    /** @var ObjectProphecy<EntityRepository<SnippetDimensionContentInterface>> */
    private ObjectProphecy $snippetDimensionContentRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->referenceRepository = $this->prophesize(ReferenceRepositoryInterface::class);
        $this->contentViewResolver = $this->prophesize(ContentViewResolverInterface::class);
        $this->contentMerger = $this->prophesize(ContentMergerInterface::class);
        /** @var ObjectProphecy<EntityRepository<SnippetDimensionContentInterface>> $prophecy */
        $prophecy = $this->prophesize(EntityRepository::class);
        $this->snippetDimensionContentRepository = $prophecy;

        $this->entityManager->getRepository(SnippetDimensionContentInterface::class)
            ->willReturn($this->snippetDimensionContentRepository->reveal());

        $this->refresher = new SnippetReferenceRefresher(
            $this->entityManager->reveal(),
            $this->referenceRepository->reveal(),
            $this->contentViewResolver->reveal(),
            $this->contentMerger->reveal()
        );
    }

    public function testGetResourceKey(): void
    {
        $this->assertSame(Snippet::RESOURCE_KEY, SnippetReferenceRefresher::getResourceKey());
    }

    public function testRefreshReturnsGenerator(): void
    {
        // Create a simple mock that returns empty results
        $queryBuilder = $this->prophesize(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->prophesize(\Doctrine\ORM\AbstractQuery::class);

        // Mock the query builder chain
        $queryBuilder->where('dimensionContent.version = :version')->willReturn($queryBuilder);
        $queryBuilder->setParameter('version', DimensionContentInterface::CURRENT_VERSION)->willReturn($queryBuilder);
        $queryBuilder->orderBy('dimensionContent.snippet', 'ASC')->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query->reveal());

        $query->toIterable()->willReturn(new \ArrayIterator([]));

        $this->snippetDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->willReturn($queryBuilder->reveal());

        $generator = $this->refresher->refresh();

        $results = \iterator_to_array($generator);
        $this->assertEmpty($results);
    }

    public function testRefreshWithFilter(): void
    {
        // Test that filter parameters are applied to the query
        $filter = ['resourceId' => '123', 'resourceKey' => 'snippets', 'locale' => 'en', 'stage' => 'live'];

        $queryBuilder = $this->prophesize(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->prophesize(\Doctrine\ORM\AbstractQuery::class);

        $queryBuilder->where('dimensionContent.version = :version')->willReturn($queryBuilder);
        $queryBuilder->setParameter('version', DimensionContentInterface::CURRENT_VERSION)->willReturn($queryBuilder);
        $queryBuilder->orderBy('dimensionContent.snippet', 'ASC')->willReturn($queryBuilder);
        $queryBuilder->join('dimensionContent.snippet', 'snippet', \Doctrine\ORM\Query\Expr\Join::WITH, 'snippet.uuid = :resourceId')->willReturn($queryBuilder);
        $queryBuilder->andWhere('dimensionContent.locale = :locale OR dimensionContent.locale IS NULL')->willReturn($queryBuilder);
        $queryBuilder->andWhere('dimensionContent.stage = :stage')->willReturn($queryBuilder);
        $queryBuilder->setParameter('resourceId', '123')->willReturn($queryBuilder);
        $queryBuilder->setParameter('locale', 'en')->willReturn($queryBuilder);
        $queryBuilder->setParameter('stage', 'live')->willReturn($queryBuilder);
        $queryBuilder->getQuery()->willReturn($query->reveal());

        $query->toIterable()->willReturn(new \ArrayIterator([]));

        $this->snippetDimensionContentRepository->createQueryBuilder('dimensionContent')
            ->willReturn($queryBuilder->reveal());

        $generator = $this->refresher->refresh($filter);
        $results = \iterator_to_array($generator);

        $this->assertEmpty($results);
    }
}
