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

namespace Sulu\Article\Tests\Functional\Infrastructure\Sulu\Content;

use Sulu\Article\Application\Message\ApplyWorkflowTransitionArticleMessage;
use Sulu\Article\Application\Message\CreateArticleMessage;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Route\Domain\Value\RequestAttributeEnum;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Tests for the ArticleSmartContentProvider.
 *
 * @phpstan-type ArticleData array{
 *     title?: string,
 *     url?: string,
 *     template?: string,
 *     locale?: string,
 *     excerptCategories?: int[],
 *     excerptTags?: string[],
 *     author?: int|null,
 *     authored?: string|null,
 * }
 *
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class ArticleSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;
    use HandleTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, ArticleInterface>
     */
    private static array $articles = [];

    /**
     * @var array<string, CategoryInterface>
     */
    private static array $categories = [];

    /**
     * @var array<string, string>
     */
    private static array $tags = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageBus = $this->getContainer()->get('sulu_message_bus');
        $this->smartContentProvider = $this->getContainer()->get('sulu_article.article_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // TODO this should not be necessary
        $requestContext = $container->get('router.request_context');
        $requestContext->setParameter(RequestAttributeEnum::SITE->value, 'sulu-io');
        // TODO this should not be necessary

        // Create categories
        self::$categories['tech'] = self::createCategory(['en' => ['title' => 'Technology']]);
        self::$categories['sports'] = self::createCategory(['en' => ['title' => 'Sports']]);
        self::$categories['health'] = self::createCategory(['en' => ['title' => 'Health']]);
        self::$categories['business'] = self::createCategory(['en' => ['title' => 'Business']]);
        self::$categories['entertainment'] = self::createCategory(['en' => ['title' => 'Entertainment']]);
        $entityManager->flush();

        // Create tags
        self::$tags = [
            'mobile' => 'mobile',
            'web' => 'web',
            'cloud' => 'cloud',
            'football' => 'football',
            'tennis' => 'tennis',
            'fitness' => 'fitness',
            'diet' => 'diet',
            'startup' => 'startup',
            'finance' => 'finance',
            'movies' => 'movies',
            'music' => 'music',
        ];

        // Create articles with various combinations of categories and tags
        self::$articles['tech1'] = self::createArticle([
            'title' => 'Latest in Tech',
            'excerptCategories' => [self::$categories['tech']->getId()],
            'excerptTags' => [self::$tags['mobile'], self::$tags['web']],
            'authored' => '2023-01-15T12:00:00+00:00',
        ]);

        self::$articles['tech2'] = self::createArticle([
            'title' => 'Cloud Computing',
            'excerptCategories' => [self::$categories['tech']->getId(), self::$categories['business']->getId()],
            'excerptTags' => [self::$tags['cloud']],
            'authored' => '2023-02-20T14:30:00+00:00',
        ]);

        self::$articles['sports1'] = self::createArticle([
            'title' => 'Football Season',
            'excerptCategories' => [self::$categories['sports']->getId()],
            'excerptTags' => [self::$tags['football']],
            'authored' => '2023-03-10T09:15:00+00:00',
        ]);

        self::$articles['sports2'] = self::createArticle([
            'title' => 'Tennis Championship',
            'excerptCategories' => [self::$categories['sports']->getId()],
            'excerptTags' => [self::$tags['tennis']],
            'authored' => '2023-04-05T16:45:00+00:00',
        ]);

        self::$articles['health1'] = self::createArticle([
            'title' => 'Fitness Tips',
            'excerptCategories' => [self::$categories['health']->getId()],
            'excerptTags' => [self::$tags['fitness']],
            'authored' => '2023-05-12T08:20:00+00:00',
        ]);

        self::$articles['health2'] = self::createArticle([
            'title' => 'Healthy Diet',
            'excerptCategories' => [self::$categories['health']->getId()],
            'excerptTags' => [self::$tags['diet'], self::$tags['fitness']],
            'authored' => '2023-06-18T11:30:00+00:00',
        ]);

        self::$articles['business1'] = self::createArticle([
            'title' => 'Startup News',
            'excerptCategories' => [self::$categories['business']->getId()],
            'excerptTags' => [self::$tags['startup']],
            'authored' => '2023-07-22T10:00:00+00:00',
        ]);

        self::$articles['business2'] = self::createArticle([
            'title' => 'Finance Report',
            'excerptCategories' => [self::$categories['business']->getId()],
            'excerptTags' => [self::$tags['finance']],
            'authored' => '2023-08-30T13:45:00+00:00',
        ]);

        self::$articles['entertainment1'] = self::createArticle([
            'title' => 'Movie Reviews',
            'excerptCategories' => [self::$categories['entertainment']->getId()],
            'excerptTags' => [self::$tags['movies']],
            'authored' => '2023-09-05T15:30:00+00:00',
        ]);

        self::$articles['entertainment2'] = self::createArticle([
            'title' => 'Music Festival',
            'excerptCategories' => [self::$categories['entertainment']->getId()],
            'excerptTags' => [self::$tags['music']],
            'authored' => '2023-10-12T17:15:00+00:00',
        ]);

        self::$articles['tech_health'] = self::createArticle([
            'title' => 'Tech in Healthcare',
            'excerptCategories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
            'excerptTags' => [self::$tags['mobile'], self::$tags['fitness']],
            'authored' => '2023-11-08T09:40:00+00:00',
        ]);

        self::$articles['sports_health'] = self::createArticle([
            'title' => 'Sports Nutrition',
            'excerptCategories' => [self::$categories['sports']->getId(), self::$categories['health']->getId()],
            'excerptTags' => [self::$tags['fitness'], self::$tags['diet']],
            'authored' => '2023-12-01T14:20:00+00:00',
        ]);

        self::$articles['business_tech'] = self::createArticle([
            'title' => 'Tech Investments',
            'excerptCategories' => [self::$categories['business']->getId(), self::$categories['tech']->getId()],
            'excerptTags' => [self::$tags['startup'], self::$tags['cloud']],
            'authored' => '2024-01-10T11:00:00+00:00',
        ]);

        self::$articles['entertainment_business'] = self::createArticle([
            'title' => 'Entertainment Industry',
            'excerptCategories' => [self::$categories['entertainment']->getId(), self::$categories['business']->getId()],
            'excerptTags' => [self::$tags['movies'], self::$tags['finance']],
            'authored' => '2024-02-15T16:30:00+00:00',
        ]);

        self::$articles['multi_category_multi_tag'] = self::createArticle([
            'title' => 'Digital Lifestyle',
            'excerptCategories' => [
                self::$categories['tech']->getId(),
                self::$categories['health']->getId(),
                self::$categories['entertainment']->getId(),
            ],
            'excerptTags' => [self::$tags['mobile'], self::$tags['fitness'], self::$tags['music']],
            'authored' => '2024-03-20T10:45:00+00:00',
        ]);
    }

    public function testFindFlatByNoParameters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], []);

        $this->assertCount(15, $result);
        $count = $this->smartContentProvider->countBy([...$this->getDefaultFilters(), ...['locale' => 'en']]);
        $this->assertSame(15, $count);

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify all articles are returned
        foreach (self::$articles as $article) {
            $this->assertContains($article->getUuid(), $resultIds);
        }
    }

    public function testFindFlatByCategoryFiltersSingleCategoryOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId()], 'categoryOperator' => 'OR'],
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId()], 'categoryOperator' => 'OR'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['tech1', 'tech2', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }

        // Verify other articles are not returned
        $allKeys = \array_keys(self::$articles);
        $unexpectedKeys = \array_diff($allKeys, $expectedKeys);

        foreach ($unexpectedKeys as $key) {
            $this->assertNotContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should not be in the result");
        }
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()], 'categoryOperator' => 'OR'],
        ], ['title' => 'asc']);

        // Should include tech1, tech2, health1, health2, tech_health, sports_health, business_tech, multi_category_multi_tag
        $this->assertCount(8, $result);
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()], 'categoryOperator' => 'OR'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = [
            'tech1', 'tech2', 'health1', 'health2', 'tech_health',
            'sports_health', 'business_tech', 'multi_category_multi_tag',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByCategoryFiltersSingleCategoryAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'categories' => [self::$categories['health']->getId()], 'categoryOperator' => 'AND'],
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'categories' => [self::$categories['health']->getId()], 'categoryOperator' => 'AND'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()], 'categoryOperator' => 'AND'],
        ], []);

        // Should include tech_health and multi_category_multi_tag
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()], 'categoryOperator' => 'AND'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersSingleTagOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'tags' => [self::$tags['mobile']], 'tagOperator' => 'OR'],
        ], []);

        // Should include tech1, tech_health, multi_category_multi_tag
        $this->assertCount(3, $result);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'tags' => [self::$tags['mobile']], 'tagOperator' => 'OR'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['tech1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersMultipleTagsOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'tags' => [self::$tags['mobile'], self::$tags['cloud']], 'tagOperator' => 'OR'],
        ], ['title' => 'asc']);

        // Should include tech1, tech2, tech_health, business_tech, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'tags' => [self::$tags['mobile'], self::$tags['cloud']], 'tagOperator' => 'OR'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['tech1', 'tech2', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersSingleTagAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'tags' => [self::$tags['fitness']], 'tagOperator' => 'AND'],
        ], []);

        // Should include health1, health2, tech_health, sports_health, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'tags' => [self::$tags['fitness']], 'tagOperator' => 'AND'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersMultipleTagsAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'tags' => [self::$tags['mobile'], self::$tags['fitness']], 'tagOperator' => 'AND'],
        ], []);

        // Should include tech_health, multi_category_multi_tag
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'tags' => [self::$tags['mobile'], self::$tags['fitness']], 'tagOperator' => 'AND'],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByCategoryAndTagFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'categories' => [self::$categories['health']->getId()], 'tags' => [self::$tags['fitness']]],
        ], []);

        // Should include health1, health2, tech_health, sports_health, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'categories' => [self::$categories['health']->getId()], 'tags' => [self::$tags['fitness']]],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // Verify correct articles are returned
        $expectedKeys = ['health1', 'health2', 'tech_health', 'sports_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$articles[$key]->getUuid(), $resultIds, "Article '$key' should be in the result");
        }
    }

    public function testFindFlatByLimitAndPageFirst(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'limit' => 5, 'page' => 1],
        ], [
            'title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            15,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'limit' => 5, 'page' => 1],
            ]),
        );

        $resultIds = \array_map(
            fn ($article) => $article['id'],
            $result,
        );

        // With title sort, verify the first page contains the alphabetically first 5 articles
        // Since we're sorting by title ASC, we expect these articles in the first page
        $this->assertContains(self::$articles['tech2']->getUuid(), $resultIds); // "Cloud Computing"
        $this->assertContains(self::$articles['multi_category_multi_tag']->getUuid(), $resultIds); // "Digital Lifestyle"

        // Additional check to verify result order (first article should be "Cloud Computing")
        $this->assertSame('Cloud Computing', $result[0]['title']);
    }

    public function testFindFlatByMaxPerPageAndPageSecond(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...['locale' => 'en', 'maxPerPage' => 5, 'page' => 2],
        ], [
            'sortBy' => 'title',
            'sortMethod' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            15,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...['locale' => 'en', 'maxPerPage' => 5, 'page' => 2],
            ]),
        );
    }

    public function testSortByTitleAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'title' => 'asc',
        ]);

        $this->assertCount(15, $result);
        // Check if first article is alphabetically first
        $this->assertStringContainsString('Cloud', $result[0]['title']);
        // Check if last article is alphabetically last
        $this->assertStringContainsString('Tennis', $result[14]['title']);
    }

    public function testSortByTitleDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'title' => 'desc',
        ]);

        $this->assertCount(15, $result);
        // Check if first article is alphabetically last
        $this->assertStringContainsString('Tennis', $result[0]['title']);
        // Check if last article is alphabetically first
        $this->assertStringContainsString('Cloud', $result[14]['title']);
    }

    public function testSortByAuthoredAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'authored' => 'asc',
        ]);

        $this->assertCount(15, $result);
        // First should be oldest
        $this->assertSame('Latest in Tech', $result[0]['title']);
        // Last should be newest
        $this->assertSame('Digital Lifestyle', $result[14]['title']);
    }

    public function testSortByAuthoredDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'authored' => 'desc',
        ]);

        $this->assertCount(15, $result);
        // First should be newest
        $this->assertSame('Digital Lifestyle', $result[0]['title']);
        // Last should be oldest
        $this->assertSame('Latest in Tech', $result[14]['title']);
    }

    /**
     * @param ArticleData $data
     */
    private static function createArticle(
        array $data = [],
    ): ArticleInterface {
        $data = \array_merge([
            'title' => 'Example Article',
            'url' => 'example-article-' . \uniqid(),
            'template' => 'article',
            'locale' => 'en',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CreateArticleMessage($data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var ArticleInterface $article */
        $article = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionArticleMessage(
                    identifier: ['uuid' => $article->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        return $article;
    }

    /**
     * @return SmartContentBaseFilters
     */
    private function getDefaultFilters(): array
    {
        return [
            'categories' => [],
            'categoryOperator' => 'OR',
            'websiteCategories' => [],
            'websiteCategoryOperator' => 'OR',
            'tags' => [],
            'tagOperator' => 'OR',
            'websiteTags' => [],
            'websiteTagOperator' => 'OR',
            'types' => [],
            'typesOperator' => 'OR',
            'locale' => 'en',
            'webspaceKey' => null,
            'dataSource' => null,
            'limit' => null,
            'page' => 1,
            'maxPerPage' => null,
            'includeSubFolders' => false,
            'excludeDuplicates' => false,
        ];
    }
}
