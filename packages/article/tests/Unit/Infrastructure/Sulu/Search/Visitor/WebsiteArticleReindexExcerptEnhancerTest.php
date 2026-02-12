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

use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexExcerptEnhancer;

class WebsiteArticleReindexExcerptEnhancerTest extends TestCase
{
    use ProphecyTrait;

    private WebsiteArticleReindexExcerptEnhancer $enhancer;

    protected function setUp(): void
    {
        $this->enhancer = new WebsiteArticleReindexExcerptEnhancer();
    }

    public function testEnhanceQueryAddsExcerptData(): void
    {
        $queryBuilder = $this->prophesize(QueryBuilder::class);
        $queryBuilder->addSelect('dimensionContent.excerptData')->willReturn($queryBuilder)->shouldBeCalled();

        $this->enhancer->enhanceQuery($queryBuilder->reveal());
    }

    public function testExcerptDataIsNullReturnsUnchanged(): void
    {
        $queryResult = ['excerptData' => null];
        $document = ['content' => [], 'title' => '', 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertSame('', $returnedData['title']);
        $this->assertSame('', $returnedData['mediaId']);
    }

    public function testExcerptDataMissingReturnsUnchanged(): void
    {
        $queryResult = [];
        $document = ['content' => [], 'title' => '', 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertSame('', $returnedData['title']);
        $this->assertSame('', $returnedData['mediaId']);
    }

    public function testExcerptTitleSetsExcerptTitleAndFallsBackToTitle(): void
    {
        $queryResult = [
            'excerptData' => ['title' => 'Excerpt Title'],
        ];
        $document = ['content' => [], 'title' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('Excerpt Title', $returnedData['title']);
    }

    public function testExcerptTitleDoesNotOverrideExistingTitle(): void
    {
        $queryResult = [
            'excerptData' => ['title' => 'Excerpt Title'],
        ];
        $document = ['content' => [], 'title' => 'Tagged Title'];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('Tagged Title', $returnedData['title']);
    }

    public function testExcerptTitleEmptyIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['title' => ''],
        ];
        $document = ['content' => [], 'title' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
        $this->assertSame('', $returnedData['title']);
    }

    public function testExcerptTitleWhitespaceIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['title' => '   '],
        ];
        $document = ['content' => [], 'title' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptDescriptionSetsField(): void
    {
        $queryResult = [
            'excerptData' => ['description' => '<p>Some <strong>rich</strong> description</p>'],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('<p>Some <strong>rich</strong> description</p>', $excerpt['description']);
    }

    public function testExcerptDescriptionEmptyIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['description' => ''],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptMoreSetsField(): void
    {
        $queryResult = [
            'excerptData' => ['more' => 'Read more'],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('Read more', $excerpt['more']);
    }

    public function testExcerptMoreEmptyIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['more' => ''],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptImageSetsExcerptImageIdAndFallsBackToMediaId(): void
    {
        $queryResult = [
            'excerptData' => ['image' => ['id' => 99]],
        ];
        $document = ['content' => [], 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame(99, $excerpt['imageId']);
        $this->assertSame('99', $returnedData['mediaId']);
    }

    public function testExcerptImageDoesNotOverrideExistingMediaId(): void
    {
        $queryResult = [
            'excerptData' => ['image' => ['id' => 99]],
        ];
        $document = ['content' => [], 'mediaId' => '42'];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame(99, $excerpt['imageId']);
        $this->assertSame('42', $returnedData['mediaId']);
    }

    public function testExcerptImageEmptyIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['image' => []],
        ];
        $document = ['content' => [], 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptImageNonNumericIdIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['image' => ['id' => 'invalid']],
        ];
        $document = ['content' => [], 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptIconSetsField(): void
    {
        $queryResult = [
            'excerptData' => ['icon' => ['id' => 77]],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame(77, $excerpt['iconId']);
    }

    public function testExcerptIconEmptyIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['icon' => []],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptIconNonNumericIdIsSkipped(): void
    {
        $queryResult = [
            'excerptData' => ['icon' => ['id' => 'invalid']],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertArrayNotHasKey('metadata', $returnedData);
    }

    public function testExcerptTitleAddedToContent(): void
    {
        $queryResult = [
            'excerptData' => ['title' => 'Excerpt Title'],
        ];
        $document = ['content' => ['existing content'], 'title' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertIsArray($returnedData['content']);
        $this->assertContains('Excerpt Title', $returnedData['content']);
        $this->assertContains('existing content', $returnedData['content']);
    }

    public function testExcerptDescriptionAddedToContentStripped(): void
    {
        $queryResult = [
            'excerptData' => ['description' => '<p>Some <strong>rich</strong> description</p>'],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);

        $this->assertIsArray($returnedData['content']);
        $this->assertContains('Some rich description', $returnedData['content']);
    }

    public function testExcerptDescriptionInMetadataKeepsHtml(): void
    {
        $queryResult = [
            'excerptData' => ['description' => '<p>Some <strong>rich</strong> description</p>'],
        ];
        $document = ['content' => []];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('<p>Some <strong>rich</strong> description</p>', $excerpt['description']);
    }

    public function testAllExcerptFieldsPopulated(): void
    {
        $queryResult = [
            'excerptData' => [
                'title' => 'Excerpt Title',
                'description' => '<p>Description</p>',
                'more' => 'Read more',
                'image' => ['id' => 55],
                'icon' => ['id' => 33],
            ],
        ];
        $document = ['content' => [], 'title' => '', 'mediaId' => ''];

        $returnedData = $this->enhancer->enhanceDocument($queryResult, $document);
        $excerpt = $this->getExcerpt($returnedData);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('Excerpt Title', $returnedData['title']);
        $this->assertSame('<p>Description</p>', $excerpt['description']);
        $this->assertSame('Read more', $excerpt['more']);
        $this->assertSame(55, $excerpt['imageId']);
        $this->assertSame('55', $returnedData['mediaId']);
        $this->assertSame(33, $excerpt['iconId']);
    }

    /**
     * @param array<string, mixed> $returnedData
     *
     * @return array<string, mixed>
     */
    private function getExcerpt(array $returnedData): array
    {
        $metadata = $returnedData['metadata'] ?? null;
        $this->assertIsArray($metadata);

        $excerpt = $metadata['excerpt'] ?? null;
        $this->assertIsArray($excerpt);

        /** @var array<string, mixed> $excerpt */
        return $excerpt;
    }
}
