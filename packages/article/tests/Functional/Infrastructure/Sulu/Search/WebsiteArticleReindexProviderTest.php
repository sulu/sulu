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

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexExcerptEnhancer;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexTaxonomyEnhancer;
use Sulu\Article\Infrastructure\Sulu\Search\WebsiteArticleReindexProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;

/**
 * @phpstan-type ArticleData array{
 *     title?: string,
 *     url?: string,
 *     template?: string,
 *     locale?: string,
 *     excerptCategories?: int[],
 *     excerptTags?: int[],
 *     author?: int|null,
 *     authored?: string|null,
 * }
 */
class WebsiteArticleReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;
    use CreateArticleTrait;
    use CreateCategoryTrait;
    use CreateTagTrait;

    private EntityManagerInterface $entityManager;
    private WebsiteArticleReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new WebsiteArticleReindexProvider($this->entityManager, []);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('website', WebsiteArticleReindexProvider::getIndex());
    }

    public function testTotal(): void
    {
        $this->assertNull($this->provider->total());
    }

    public function testProvideAll(): void
    {
        $article1 = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article One EN',
                    'url' => '/test-article',
                    'mainWebspace' => 'sulu-io',
                    'additionalWebspaces' => ['blog'],
                    'authored' => '1995-11-29T12:00:00+00:00',
                    'customizeWebspaceSettings' => true,
                ],
            ],
        ]);

        $article2 = static::createArticle([
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO DE',
                    'url' => '/test-article-two',
                    'mainWebspace' => 'blog',
                    'customizeWebspaceSettings' => true,
                ],
            ],
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO EN',
                    'url' => '/test-article-two-en',
                    'customizeWebspaceSettings' => false,
                ],
            ],
        ]);

        $article3 = static::createArticle([
            'de' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Draft Article',
                    'url' => '/draft-article',
                    'mainWebspace' => 'blog',
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, created = :created WHERE articleUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $article1->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, authored = :authored WHERE articleUuid = :uuid';
        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'authored' => null,
            'uuid' => $article2->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article1->getUuid() . '__en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article1->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io', 'blog'],
                'title' => 'Article One EN',
                'url' => '/test-article',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable('1995-11-29 12:00:00'))->format('c'),
                'metadata' => [],
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article2->getUuid() . '__de',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'de',
                'webspaces' => ['blog'],
                'title' => 'Article TWO DE',
                'url' => '/test-article-two',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'metadata' => [],
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article2->getUuid() . '__en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO EN',
                'url' => '/test-article-two-en',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'metadata' => [],
            ],
        ];

        \usort($expectedResult, fn ($a, $b) => \strcmp($a['id'], $b['id']));
        \usort($results, fn ($a, $b) => \strcmp($a['id'], $b['id']));

        $this->assertSame(
            $expectedResult,
            $results,
        );
    }

    public function testProvideAllNoWebspaces(): void
    {
        $article1 = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article One EN',
                    'url' => '/test-article',
                    'authored' => '1995-11-29T12:00:00+00:00',
                    'customizeWebspaceSettings' => false,
                ],
            ],
        ]);

        $article2 = static::createArticle([
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO DE',
                    'url' => '/test-article-two',
                    'customizeWebspaceSettings' => false,
                ],
            ],
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO EN',
                    'url' => '/test-article-two-en',
                    'customizeWebspaceSettings' => false,
                ],
            ],
        ]);

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, created = :created WHERE articleUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $article1->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, created = :created, authored = :authored WHERE articleUuid = :uuid';
        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'authored' => null,
            'uuid' => $article2->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article1->getUuid() . '__en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article1->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article One EN',
                'url' => '/test-article',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable('1995-11-29 12:00:00'))->format('c'),
                'metadata' => [],
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article2->getUuid() . '__de',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'de',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO DE',
                'url' => '/test-article-two',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'metadata' => [],
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '__' . $article2->getUuid() . '__en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO EN',
                'url' => '/test-article-two-en',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'metadata' => [],
            ],
        ];

        \usort($expectedResult, fn ($a, $b) => \strcmp($a['id'], $b['id']));
        \usort($results, fn ($a, $b) => \strcmp($a['id'], $b['id']));

        $this->assertSame(
            $expectedResult,
            $results,
        );
    }

    public function testProvideWithSpecificIdentifiers(): void
    {
        $article1 = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article One EN',
                    'url' => '/test-article',
                ],
            ],
        ]);

        $article2 = static::createArticle([
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO DE',
                    'url' => '/test-article-two',
                ],
            ],
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article TWO EN',
                    'url' => '/test-article-two-en',
                ],
            ],
        ]);

        $article3 = static::createArticle([
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article THREE DE',
                    'url' => '/test-article-three',
                ],
            ],
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article THREE EN',
                    'url' => '/test-article-three-en',
                ],
            ],
        ]);

        $identifiers = [
            ArticleInterface::RESOURCE_KEY . '__' . $article1->getUuid() . '__en',
            ArticleInterface::RESOURCE_KEY . '__' . $article2->getUuid() . '__de',
        ];

        $config = ReindexConfig::create()
            ->withIndex('website')
            ->withIdentifiers($identifiers);

        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(2, $results);

        $resultTitles = \array_column($results, 'title');
        $this->assertContains('Article One EN', $resultTitles);
        $this->assertContains('Article TWO DE', $resultTitles);
        $this->assertNotContains('Article TWO EN', $resultTitles);
        $this->assertNotContains('Article THREE DE', $resultTitles);
        $this->assertNotContains('Article THREE EN', $resultTitles);
    }

    public function testExcerptEnhancerPopulatesExcerptProperties(): void
    {
        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexExcerptEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Excerpt',
                    'url' => '/article-excerpt',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => '<p>Excerpt <strong>description</strong></p>',
                        'more' => 'Read more',
                        'image' => ['id' => 1],
                        'icon' => ['id' => 2],
                    ],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('Excerpt Title', $result['title']);

        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('<p>Excerpt <strong>description</strong></p>', $excerpt['description']);
        $this->assertSame('Read more', $excerpt['more']);
        $this->assertSame(1, $excerpt['imageId']);
        $this->assertSame(2, $excerpt['iconId']);
    }

    public function testExcerptTitleFallbackWhenNoContentTitle(): void
    {
        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexExcerptEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Original Title',
                    'url' => '/article-title-fallback',
                    'excerpt' => [
                        'title' => 'Excerpt Title Override',
                    ],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('Excerpt Title Override', $result['title']);

        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);
        $this->assertSame('Excerpt Title Override', $excerpt['title']);
    }

    public function testExcerptImageFallbackToMediaId(): void
    {
        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexExcerptEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Excerpt Image',
                    'url' => '/article-excerpt-image',
                    'excerpt' => [
                        'image' => ['id' => 55],
                    ],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('55', $result['mediaId']);

        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);
        $this->assertSame(55, $excerpt['imageId']);
    }

    public function testNoExcerptDataProducesEmptyProperties(): void
    {
        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexExcerptEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'No Excerpt Article',
                    'url' => '/no-excerpt',
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertSame('No Excerpt Article', $result['title']);
        $this->assertSame([], $result['metadata']);
    }

    public function testTaxonomyEnhancerPopulatesCategoriesAndTags(): void
    {
        $category1 = static::createCategory(['en' => ['title' => 'Category 1']]);
        $category2 = static::createCategory(['en' => ['title' => 'Category 2']]);
        $tag1 = static::createTag(['name' => 'tag-one']);
        $tag2 = static::createTag(['name' => 'tag-two']);

        $this->entityManager->flush();

        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexTaxonomyEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Taxonomy',
                    'url' => '/article-taxonomy',
                    'excerptCategories' => [$category1->getId(), $category2->getId()],
                    'excerptTags' => [$tag1->getId(), $tag2->getId()],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertSame([$category1->getId(), $category2->getId()], $excerpt['categoryIds']);
        $this->assertSame(['tag-one', 'tag-two'], $excerpt['tagNames']);
    }

    public function testTaxonomyEnhancerPopulatesOnlyCategories(): void
    {
        $category1 = static::createCategory(['en' => ['title' => 'Category 1']]);
        $category2 = static::createCategory(['en' => ['title' => 'Category 2']]);

        $this->entityManager->flush();

        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexTaxonomyEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Categories',
                    'url' => '/article-categories',
                    'excerptCategories' => [$category1->getId(), $category2->getId()],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertSame([$category1->getId(), $category2->getId()], $excerpt['categoryIds']);
        $this->assertArrayNotHasKey('tagNames', $excerpt);
    }

    public function testTaxonomyEnhancerPopulatesOnlyTags(): void
    {
        $tag1 = static::createTag(['name' => 'php']);
        $tag2 = static::createTag(['name' => 'sulu']);

        $this->entityManager->flush();

        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexTaxonomyEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Tags',
                    'url' => '/article-tags',
                    'excerptTags' => [$tag1->getId(), $tag2->getId()],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertArrayNotHasKey('categoryIds', $excerpt);
        $this->assertSame(['php', 'sulu'], $excerpt['tagNames']);
    }

    public function testTaxonomyEnhancerWithEmptyTaxonomyDoesNotAddKeys(): void
    {
        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [new WebsiteArticleReindexTaxonomyEnhancer()],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article Without Taxonomy',
                    'url' => '/article-no-taxonomy',
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertIsArray($result['metadata']);
        $this->assertArrayNotHasKey('excerpt', $result['metadata']);
    }

    public function testTaxonomyEnhancerPreservesExistingExcerptProperties(): void
    {
        $category = static::createCategory(['en' => ['title' => 'Category 1']]);
        $tag = static::createTag(['name' => 'test-tag']);

        $this->entityManager->flush();

        $provider = new WebsiteArticleReindexProvider(
            $this->entityManager,
            [
                new WebsiteArticleReindexExcerptEnhancer(),
                new WebsiteArticleReindexTaxonomyEnhancer(),
            ],
        );

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Article With Excerpt and Taxonomy',
                    'url' => '/article-excerpt-taxonomy',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => '<p>Excerpt description</p>',
                    ],
                    'excerptCategories' => [$category->getId()],
                    'excerptTags' => [$tag->getId()],
                ],
            ],
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array<string, mixed>> $results */
        $results = \iterator_to_array($provider->provide($config));

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertIsArray($result['metadata']);
        $excerpt = $result['metadata']['excerpt'];
        $this->assertIsArray($excerpt);

        $this->assertSame('Excerpt Title', $excerpt['title']);
        $this->assertSame('<p>Excerpt description</p>', $excerpt['description']);
        $this->assertSame([$category->getId()], $excerpt['categoryIds']);
        $this->assertSame(['test-tag'], $excerpt['tagNames']);
    }
}
