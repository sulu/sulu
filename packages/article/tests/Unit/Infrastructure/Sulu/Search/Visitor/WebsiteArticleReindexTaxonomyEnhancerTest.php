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

namespace Sulu\Article\Tests\Unit\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexTaxonomyEnhancer;

class WebsiteArticleReindexTaxonomyEnhancerTest extends TestCase
{
    use ProphecyTrait;

    public function testEnhanceQueryDoesNotModifyQuery(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $enhancer = new WebsiteArticleReindexTaxonomyEnhancer($entityManager->reveal());

        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect(Argument::any())->shouldNotBeCalled();

        $enhancer->enhanceQuery($queryBuilder->reveal());
    }

    public function testMissingDimensionContentIdReturnsUnchanged(): void
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $enhancer = new WebsiteArticleReindexTaxonomyEnhancer($entityManager->reveal());

        $document = ['title' => 'Test'];

        $result = $enhancer->enhanceDocument([], $document);

        $this->assertSame($document, $result);
    }

    public function testCategoriesPopulated(): void
    {
        $enhancer = $this->createEnhancerWithTaxonomyResult(
            categoryIds: '1,2,3',
            tagNames: null,
        );

        $document = ['title' => 'Test', 'metadata' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame([1, 2, 3], $result['metadata']['excerpt']['categoryIds']);
        $this->assertArrayNotHasKey('tagNames', $result['metadata']['excerpt']);
    }

    public function testTagsPopulated(): void
    {
        $enhancer = $this->createEnhancerWithTaxonomyResult(
            categoryIds: null,
            tagNames: 'php||sulu',
        );

        $document = ['title' => 'Test', 'metadata' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertArrayNotHasKey('categoryIds', $result['metadata']['excerpt']);
        $this->assertSame(['php', 'sulu'], $result['metadata']['excerpt']['tagNames']);
    }

    public function testBothCategoriesAndTagsPopulated(): void
    {
        $enhancer = $this->createEnhancerWithTaxonomyResult(
            categoryIds: '5,10',
            tagNames: 'cms||web',
        );

        $document = ['title' => 'Test', 'metadata' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame([5, 10], $result['metadata']['excerpt']['categoryIds']);
        $this->assertSame(['cms', 'web'], $result['metadata']['excerpt']['tagNames']);
    }

    public function testEmptyTaxonomyDoesNotAddKeys(): void
    {
        $enhancer = $this->createEnhancerWithTaxonomyResult(
            categoryIds: null,
            tagNames: null,
        );

        $document = ['title' => 'Test', 'metadata' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertArrayNotHasKey('excerpt', $result['metadata']);
    }

    public function testPreservesExistingExcerptMetadata(): void
    {
        $enhancer = $this->createEnhancerWithTaxonomyResult(
            categoryIds: '1',
            tagNames: 'tag1',
        );

        $document = [
            'title' => 'Test',
            'metadata' => [
                'excerpt' => [
                    'title' => 'Existing Excerpt Title',
                ],
            ],
        ];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame('Existing Excerpt Title', $result['metadata']['excerpt']['title']);
        $this->assertSame([1], $result['metadata']['excerpt']['categoryIds']);
        $this->assertSame(['tag1'], $result['metadata']['excerpt']['tagNames']);
    }

    private function createEnhancerWithTaxonomyResult(?string $categoryIds, ?string $tagNames): WebsiteArticleReindexTaxonomyEnhancer
    {
        $qb = $this->createQueryBuilderStub(['categoryIds' => $categoryIds, 'tagNames' => $tagNames]);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->createQueryBuilder()->willReturn($qb);

        return new WebsiteArticleReindexTaxonomyEnhancer($entityManager->reveal());
    }

    /**
     * @param array<string, mixed> $singleResult
     */
    private function createQueryBuilderStub(array $singleResult): QueryBuilder
    {
        $query = $this->prophesize(Query::class);
        $query->getSingleResult()->willReturn($singleResult);

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select(Argument::any())->willReturn($qb);
        $qb->addSelect(Argument::any())->willReturn($qb);
        $qb->from(Argument::any(), Argument::any())->willReturn($qb);
        $qb->leftJoin(Argument::any(), Argument::any())->willReturn($qb);
        $qb->where(Argument::any())->willReturn($qb);
        $qb->setParameter(Argument::any(), Argument::any())->willReturn($qb);
        $qb->getQuery()->willReturn($query->reveal());

        return $qb->reveal();
    }
}
