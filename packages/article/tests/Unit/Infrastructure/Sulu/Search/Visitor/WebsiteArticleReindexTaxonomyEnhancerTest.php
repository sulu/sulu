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
        $enhancer = $this->createEnhancerWithResults(
            categoryResults: [['id' => 1], ['id' => 2], ['id' => 3]],
            tagResults: [['name' => null]],
        );

        $document = ['title' => 'Test', 'properties' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['properties']);
        $this->assertIsArray($result['properties']['excerpt']);
        $this->assertSame([1, 2, 3], $result['properties']['excerpt']['categoryIds']);
        $this->assertArrayNotHasKey('tagNames', $result['properties']['excerpt']);
    }

    public function testTagsPopulated(): void
    {
        $enhancer = $this->createEnhancerWithResults(
            categoryResults: [['id' => null]],
            tagResults: [['name' => 'php'], ['name' => 'sulu']],
        );

        $document = ['title' => 'Test', 'properties' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['properties']);
        $this->assertIsArray($result['properties']['excerpt']);
        $this->assertArrayNotHasKey('categoryIds', $result['properties']['excerpt']);
        $this->assertSame(['php', 'sulu'], $result['properties']['excerpt']['tagNames']);
    }

    public function testBothCategoriesAndTagsPopulated(): void
    {
        $enhancer = $this->createEnhancerWithResults(
            categoryResults: [['id' => 5], ['id' => 10]],
            tagResults: [['name' => 'cms'], ['name' => 'web']],
        );

        $document = ['title' => 'Test', 'properties' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['properties']);
        $this->assertIsArray($result['properties']['excerpt']);
        $this->assertSame([5, 10], $result['properties']['excerpt']['categoryIds']);
        $this->assertSame(['cms', 'web'], $result['properties']['excerpt']['tagNames']);
    }

    public function testEmptyTaxonomyDoesNotAddKeys(): void
    {
        $enhancer = $this->createEnhancerWithResults(
            categoryResults: [['id' => null]],
            tagResults: [['name' => null]],
        );

        $document = ['title' => 'Test', 'properties' => []];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['properties']);
        $this->assertArrayNotHasKey('excerpt', $result['properties']);
    }

    public function testPreservesExistingExcerptProperties(): void
    {
        $enhancer = $this->createEnhancerWithResults(
            categoryResults: [['id' => 1]],
            tagResults: [['name' => 'tag1']],
        );

        $document = [
            'title' => 'Test',
            'properties' => [
                'excerpt' => [
                    'title' => 'Existing Excerpt Title',
                ],
            ],
        ];
        $result = $enhancer->enhanceDocument(['dimensionContentId' => 42], $document);

        $this->assertIsArray($result['properties']);
        $this->assertIsArray($result['properties']['excerpt']);
        $this->assertSame('Existing Excerpt Title', $result['properties']['excerpt']['title']);
        $this->assertSame([1], $result['properties']['excerpt']['categoryIds']);
        $this->assertSame(['tag1'], $result['properties']['excerpt']['tagNames']);
    }

    /**
     * @param list<array{id: int|null}> $categoryResults
     * @param list<array{name: string|null}> $tagResults
     */
    private function createEnhancerWithResults(array $categoryResults, array $tagResults): WebsiteArticleReindexTaxonomyEnhancer
    {
        $categoryQb = $this->createQueryBuilderStub($categoryResults);
        $tagQb = $this->createQueryBuilderStub($tagResults);

        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $entityManager->createQueryBuilder()->willReturn($categoryQb, $tagQb);

        return new WebsiteArticleReindexTaxonomyEnhancer($entityManager->reveal());
    }

    /**
     * @param list<array<string, mixed>> $scalarResult
     */
    private function createQueryBuilderStub(array $scalarResult): QueryBuilder
    {
        $query = $this->prophesize(Query::class);
        $query->getScalarResult()->willReturn($scalarResult);

        $qb = $this->prophesize(QueryBuilder::class);
        $qb->select(Argument::any())->willReturn($qb);
        $qb->from(Argument::any(), Argument::any())->willReturn($qb);
        $qb->leftJoin(Argument::any(), Argument::any())->willReturn($qb);
        $qb->where(Argument::any())->willReturn($qb);
        $qb->setParameter(Argument::any(), Argument::any())->willReturn($qb);
        $qb->getQuery()->willReturn($query->reveal());

        return $qb->reveal();
    }
}
