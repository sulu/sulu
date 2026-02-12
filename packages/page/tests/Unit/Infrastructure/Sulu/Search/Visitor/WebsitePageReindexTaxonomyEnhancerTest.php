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

namespace Sulu\Page\Tests\Unit\Infrastructure\Sulu\Search\Visitor;

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Page\Infrastructure\Sulu\Search\Visitor\WebsitePageReindexTaxonomyEnhancer;

class WebsitePageReindexTaxonomyEnhancerTest extends TestCase
{
    use ProphecyTrait;

    private WebsitePageReindexTaxonomyEnhancer $enhancer;

    protected function setUp(): void
    {
        $this->enhancer = new WebsitePageReindexTaxonomyEnhancer();
    }

    public function testEnhanceQueryAddsJoinsAndGroupConcat(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect('dimensionContent.id AS dimensionContentId')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->leftJoin('dimensionContent.excerptCategories', 'category')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->leftJoin('dimensionContent.excerptTags', 'tag')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->addSelect('GROUP_CONCAT(DISTINCT category.id) AS categoryIds')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->addSelect('GROUP_CONCAT(DISTINCT tag.name) AS tagNames')->willReturn($queryBuilder)->shouldBeCalled();
        $queryBuilder->addGroupBy('dimensionContent.id')->willReturn($queryBuilder)->shouldBeCalled();

        $this->enhancer->enhanceQuery($queryBuilder->reveal());
    }

    public function testMissingTaxonomyDataReturnsUnchanged(): void
    {
        $document = ['title' => 'Test', 'metadata' => []];

        $result = $this->enhancer->enhanceDocument([], $document);

        $this->assertSame($document, $result);
    }

    public function testCategoriesPopulated(): void
    {
        $document = ['title' => 'Test', 'metadata' => []];
        $result = $this->enhancer->enhanceDocument(['categoryIds' => '1,2,3', 'tagNames' => null], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame([1, 2, 3], $result['metadata']['excerpt']['categoryIds']);
        $this->assertArrayNotHasKey('tagNames', $result['metadata']['excerpt']);
    }

    public function testTagsPopulated(): void
    {
        $document = ['title' => 'Test', 'metadata' => []];
        $result = $this->enhancer->enhanceDocument(['categoryIds' => null, 'tagNames' => 'php,sulu'], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertArrayNotHasKey('categoryIds', $result['metadata']['excerpt']);
        $this->assertSame(['php', 'sulu'], $result['metadata']['excerpt']['tagNames']);
    }

    public function testBothCategoriesAndTagsPopulated(): void
    {
        $document = ['title' => 'Test', 'metadata' => []];
        $result = $this->enhancer->enhanceDocument(['categoryIds' => '5,10', 'tagNames' => 'cms,web'], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame([5, 10], $result['metadata']['excerpt']['categoryIds']);
        $this->assertSame(['cms', 'web'], $result['metadata']['excerpt']['tagNames']);
    }

    public function testEmptyTaxonomyDoesNotAddKeys(): void
    {
        $document = ['title' => 'Test', 'metadata' => []];
        $result = $this->enhancer->enhanceDocument(['categoryIds' => null, 'tagNames' => null], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertArrayNotHasKey('excerpt', $result['metadata']);
    }

    public function testPreservesExistingExcerptMetadata(): void
    {
        $document = [
            'title' => 'Test',
            'metadata' => [
                'excerpt' => [
                    'title' => 'Existing Excerpt Title',
                ],
            ],
        ];
        $result = $this->enhancer->enhanceDocument(['categoryIds' => '1', 'tagNames' => 'tag1'], $document);

        $this->assertIsArray($result['metadata']);
        $this->assertIsArray($result['metadata']['excerpt']);
        $this->assertSame('Existing Excerpt Title', $result['metadata']['excerpt']['title']);
        $this->assertSame([1], $result['metadata']['excerpt']['categoryIds']);
        $this->assertSame(['tag1'], $result['metadata']['excerpt']['tagNames']);
    }
}
