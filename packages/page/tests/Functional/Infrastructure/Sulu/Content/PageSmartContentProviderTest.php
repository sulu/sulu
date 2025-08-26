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

namespace Sulu\Page\Tests\Functional\Infrastructure\Sulu\Content;

use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
use Sulu\Page\Application\Message\ModifyPageMessage;
use Sulu\Page\Application\MessageHandler\CreatePageMessageHandler;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/**
 * Tests for the PageSmartContentProvider.
 *
 * @phpstan-type PageData array{
 *     title?: string,
 *     url?: string,
 *     template?: string,
 *     locale?: string,
 *     excerptCategories?: int[],
 *     excerptTags?: string[],
 *     parent?: string|null,
 *     author?: int|null,
 *     authored?: string|null,
 * }
 *
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class PageSmartContentProviderTest extends SuluTestCase
{
    use CreateCategoryTrait;
    use CreateTagTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, PageInterface>
     */
    private static array $pages = [];

    /**
     * @var array<string, CategoryInterface>
     */
    private static array $categories = [];

    /**
     * @var array<string, string>
     */
    private static array $tags = [];

    /**
     * @var array<string>
     */
    private static array $webspaces = ['sulu-io', 'blog'];

    /**
     * @var array<string, string>
     */
    private static array $parentPages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->smartContentProvider = $this->getContainer()->get('sulu_page.page_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

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

        // Create parent pages for testing dataSource filter
        foreach (self::$webspaces as $webspaceKey) {
            // Create parent pages first
            $parentData = [
                'title' => 'Parent Page ' . $webspaceKey,
                'url' => '/',
                'template' => 'default',
                'locale' => 'en',
                'authored' => '2023-01-14T12:00:00+00:00',
            ];

            $parentPage = self::createPage($webspaceKey, CreatePageMessageHandler::HOMEPAGE_PARENT_ID, $parentData, ['de']);
            self::$parentPages[$webspaceKey] = $parentPage->getUuid();
        }

        // Create pages with various combinations of categories, tags, templates, and webspaces
        // Technology pages
        self::$pages['tech1'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Latest in Tech',
                'excerptCategories' => [self::$categories['tech']->getId()],
                'excerptTags' => [self::$tags['mobile'], self::$tags['web']],
                'authored' => '2023-01-15T12:00:00+00:00',
                'template' => 'default',
            ],
        );

        self::$pages['tech2'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Cloud Computing',
                'excerptCategories' => [self::$categories['tech']->getId(), self::$categories['business']->getId()],
                'excerptTags' => [self::$tags['cloud']],
                'authored' => '2023-02-20T14:30:00+00:00',
                'template' => 'blog',
            ],
        );

        // Sports pages
        self::$pages['sports1'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Football Season',
                'excerptCategories' => [self::$categories['sports']->getId()],
                'excerptTags' => [self::$tags['football']],
                'authored' => '2023-03-10T09:15:00+00:00',
                'template' => 'default',
            ],
        );

        self::$pages['sports2'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Tennis Championship',
                'excerptCategories' => [self::$categories['sports']->getId()],
                'excerptTags' => [self::$tags['tennis']],
                'authored' => '2023-04-05T16:45:00+00:00',
                'template' => 'landing_page',
            ],
        );

        // Health pages
        self::$pages['health1'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Fitness Tips',
                'excerptCategories' => [self::$categories['health']->getId()],
                'excerptTags' => [self::$tags['fitness']],
                'authored' => '2023-05-12T08:20:00+00:00',
                'template' => 'default',
            ],
        );

        self::$pages['health2'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Healthy Diet',
                'excerptCategories' => [self::$categories['health']->getId()],
                'excerptTags' => [self::$tags['diet'], self::$tags['fitness']],
                'authored' => '2023-06-18T11:30:00+00:00',
                'template' => 'blog',
            ],
        );

        // Business pages
        self::$pages['business1'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Startup News',
                'excerptCategories' => [self::$categories['business']->getId()],
                'excerptTags' => [self::$tags['startup']],
                'authored' => '2023-07-22T10:00:00+00:00',
                'template' => 'homepage',
            ],
        );

        self::$pages['business2'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Finance Report',
                'excerptCategories' => [self::$categories['business']->getId()],
                'excerptTags' => [self::$tags['finance']],
                'authored' => '2023-08-30T13:45:00+00:00',
                'template' => 'default',
            ],
        );

        // Entertainment pages
        self::$pages['entertainment1'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Movie Reviews',
                'excerptCategories' => [self::$categories['entertainment']->getId()],
                'excerptTags' => [self::$tags['movies']],
                'authored' => '2023-09-05T15:30:00+00:00',
                'template' => 'blog',
            ],
        );

        self::$pages['entertainment2'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Music Festival',
                'excerptCategories' => [self::$categories['entertainment']->getId()],
                'excerptTags' => [self::$tags['music']],
                'authored' => '2023-10-12T17:15:00+00:00',
                'template' => 'landing_page',
            ],
        );

        // Cross-category pages
        self::$pages['tech_health'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Tech in Healthcare',
                'excerptCategories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                'excerptTags' => [self::$tags['mobile'], self::$tags['fitness']],
                'authored' => '2023-11-08T09:40:00+00:00',
                'template' => 'default',
            ],
        );

        self::$pages['sports_health'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Sports Nutrition',
                'excerptCategories' => [self::$categories['sports']->getId(), self::$categories['health']->getId()],
                'excerptTags' => [self::$tags['fitness'], self::$tags['diet']],
                'authored' => '2023-12-01T14:20:00+00:00',
                'template' => 'blog',
            ],
        );

        self::$pages['business_tech'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Zero Tech Investments',
                'excerptCategories' => [self::$categories['business']->getId(), self::$categories['tech']->getId()],
                'excerptTags' => [self::$tags['startup'], self::$tags['cloud']],
                'authored' => '2024-01-10T11:00:00+00:00',
                'template' => 'landing_page',
            ],
        );

        self::$pages['entertainment_business'] = self::createPage(
            'blog',
            self::$parentPages['blog'],
            [
                'title' => 'Entertainment Industry',
                'excerptCategories' => [self::$categories['entertainment']->getId(), self::$categories['business']->getId()],
                'excerptTags' => [self::$tags['movies'], self::$tags['finance']],
                'authored' => '2024-02-15T16:30:00+00:00',
                'template' => 'homepage',
            ],
        );

        self::$pages['multi_category_multi_tag'] = self::createPage(
            'sulu-io',
            self::$parentPages['sulu-io'],
            [
                'title' => 'Digital Lifestyle',
                'excerptCategories' => [
                    self::$categories['tech']->getId(),
                    self::$categories['health']->getId(),
                    self::$categories['entertainment']->getId(),
                ],
                'excerptTags' => [self::$tags['mobile'], self::$tags['fitness'], self::$tags['music']],
                'authored' => '2024-03-20T10:45:00+00:00',
                'template' => 'default',
            ],
        );
    }

    public function testFindFlatByNoParameters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], []);

        $this->assertCount(8, $result); // 8 sulu-io pages (parent page excluded)
        $count = $this->smartContentProvider->countBy([...$this->getDefaultFilters(), ...['locale' => 'en']]);
        $this->assertSame(8, $count);

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify sulu-io pages are returned
        $expectedSuluIoPages = ['tech1', 'sports1', 'health1', 'business1', 'entertainment1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedSuluIoPages as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds);
        }

        // Verify sulu-io parent page is NOT returned (data source should not be in results)
        $this->assertNotContains(self::$parentPages['sulu-io'], $resultIds);

        // Verify blog pages are NOT returned (different data source)
        $blogPages = ['tech2', 'sports2', 'health2', 'business2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($blogPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds);
        }
    }

    public function testFindFlatByCategoryFiltersSingleCategoryOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
            ],
        ], []);

        $this->assertCount(4, $result); // Only sulu-io tech pages
        $this->assertSame(
            4,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['tech']->getId()],
                    'categoryOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog tech pages are not returned
        $this->assertNotContains(self::$pages['tech2']->getUuid(), $resultIds, "Page 'tech2' should not be in the result");
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                'categoryOperator' => 'OR',
            ],
        ], ['title' => 'asc']);

        // Should include only sulu-io pages with tech or health categories
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                    'categoryOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech1', 'health1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog pages are not returned
        $blogPages = ['tech2', 'health2', 'sports_health'];
        foreach ($blogPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the result");
        }
    }

    public function testFindFlatByCategoryFiltersSingleCategoryAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['health']->getId()],
                'categoryOperator' => 'AND',
            ],
        ], []);

        $this->assertCount(3, $result); // Only sulu-io health pages
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['health']->getId()],
                    'categoryOperator' => 'AND',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['health1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog health pages are not returned
        $this->assertNotContains(self::$pages['health2']->getUuid(), $resultIds, "Page 'health2' should not be in the result");
        $this->assertNotContains(self::$pages['sports_health']->getUuid(), $resultIds, "Page 'sports_health' should not be in the result");
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                'categoryOperator' => 'AND',
            ],
        ], []);

        // Should include only sulu-io pages with both tech and health categories
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                    'categoryOperator' => 'AND',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersSingleTagOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => [self::$tags['mobile']],
                'tagOperator' => 'OR',
            ],
        ], []);

        // Should include only sulu-io pages with mobile tag
        $this->assertCount(3, $result);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => [self::$tags['mobile']],
                    'tagOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }
    }

    public function testFindFlatByTagFiltersMultipleTagsOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => [self::$tags['mobile'], self::$tags['cloud']],
                'tagOperator' => 'OR',
            ],
        ], ['title' => 'asc']);

        // Should include only sulu-io pages with mobile or cloud tags
        $this->assertCount(4, $result);
        $this->assertSame(
            4,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => [self::$tags['mobile'], self::$tags['cloud']],
                    'tagOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog pages with cloud tag are not returned
        $this->assertNotContains(self::$pages['tech2']->getUuid(), $resultIds, "Page 'tech2' should not be in the result");
    }

    public function testFindFlatByTagFiltersSingleTagAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => [self::$tags['fitness']],
                'tagOperator' => 'AND',
            ],
        ], []);

        // Should include only sulu-io pages with fitness tag
        $this->assertCount(3, $result);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => [self::$tags['fitness']],
                    'tagOperator' => 'AND',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['health1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog pages with fitness tag are not returned
        $this->assertNotContains(self::$pages['health2']->getUuid(), $resultIds, "Page 'health2' should not be in the result");
        $this->assertNotContains(self::$pages['sports_health']->getUuid(), $resultIds, "Page 'sports_health' should not be in the result");
    }

    public function testFindFlatByTagFiltersMultipleTagsAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'tags' => [self::$tags['mobile'], self::$tags['fitness']],
                'tagOperator' => 'AND',
            ],
        ], []);

        // Should include only sulu-io pages with both mobile and fitness tags
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'tags' => [self::$tags['mobile'], self::$tags['fitness']],
                    'tagOperator' => 'AND',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }
    }

    public function testFindFlatByCategoryAndTagFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'categories' => [self::$categories['health']->getId()],
                'tags' => [self::$tags['fitness']],
            ],
        ], []);

        // Should include only sulu-io pages with health category and fitness tag
        $this->assertCount(3, $result);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'categories' => [self::$categories['health']->getId()],
                    'tags' => [self::$tags['fitness']],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['health1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the result");
        }

        // Verify blog pages are not returned
        $this->assertNotContains(self::$pages['health2']->getUuid(), $resultIds, "Page 'health2' should not be in the result");
        $this->assertNotContains(self::$pages['sports_health']->getUuid(), $resultIds, "Page 'sports_health' should not be in the result");
    }

    public function testFindFlatByLimitAndPageFirst(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'limit' => 5,
                'page' => 1,
            ],
        ], [
            'title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            8, // 8 sulu-io pages (parent page excluded)
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'limit' => 5,
                    'page' => 1,
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // With sorting by title ascending, verify the results are in the correct order
        // and only include sulu-io pages
        $this->assertSame('Digital Lifestyle', $result[0]['title']);
        $this->assertSame(self::$pages['multi_category_multi_tag']->getUuid(), $result[0]['id']);
    }

    public function testFindFlatByMaxPerPageAndPageSecond(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'page' => 2,
                'maxPerPage' => 5,
            ],
        ], [
            'title' => 'asc',
        ]);

        $this->assertCount(3, $result); // Remaining sulu-io pages
        $this->assertSame(
            8, // 8 sulu-io pages (parent page excluded)
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'page' => 2,
                    'maxPerPage' => 5,
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Blog pages should not be in the results
        $blogPages = ['tech2', 'sports2', 'health2', 'business2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($blogPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Second page should not include '$key'");
        }
    }

    public function testSortByTitleAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'title' => 'asc',
        ]);

        $this->assertCount(8, $result);

        // Check if first article is alphabetically first among sulu-io pages
        $this->assertStringContainsString('Digital Lifestyle', $result[0]['title']);
        $this->assertSame(self::$pages['multi_category_multi_tag']->getUuid(), $result[0]['id'], "First result should be 'Digital Lifestyle'");

        // Check if last article is alphabetically last among sulu-io pages
        $this->assertStringContainsString('Zero Tech Investments', $result[7]['title']);
        $this->assertSame(self::$pages['business_tech']->getUuid(), $result[7]['id'], "Last result should be 'Zero Tech Investments'");

        // Verify the order of some key pages
        $resultIds = \array_map(fn ($page) => $page['id'], $result);
        $this->assertLessThan(
            \array_search(self::$pages['health1']->getUuid(), $resultIds),
            \array_search(self::$pages['multi_category_multi_tag']->getUuid(), $resultIds),
            "'Digital Lifestyle' should come before 'Fitness Tips'"
        );
        $this->assertLessThan(
            \array_search(self::$pages['business1']->getUuid(), $resultIds),
            \array_search(self::$pages['sports1']->getUuid(), $resultIds),
            "'Football Season' should come before 'Startup News'"
        );
    }

    public function testSortByTitleDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'title' => 'desc',
        ]);

        $this->assertCount(8, $result);

        // Check if first article is alphabetically last among sulu-io pages
        $this->assertStringContainsString('Zero Tech Investments', $result[0]['title']);
        $this->assertSame(self::$pages['business_tech']->getUuid(), $result[0]['id'], "First result should be 'Zero Tech Investments'");

        // Check if last article is alphabetically first among sulu-io pages
        $this->assertStringContainsString('Digital Lifestyle', $result[7]['title']);
        $this->assertSame(self::$pages['multi_category_multi_tag']->getUuid(), $result[7]['id'], "Last result should be 'Digital Lifestyle'");

        // Verify the order of some key pages
        $resultIds = \array_map(fn ($page) => $page['id'], $result);
        $this->assertLessThan(
            \array_search(self::$pages['multi_category_multi_tag']->getUuid(), $resultIds),
            \array_search(self::$pages['business_tech']->getUuid(), $resultIds),
            "'Zero Tech Investments' should come before 'Digital Lifestyle' in descending order"
        );
        $this->assertLessThan(
            \array_search(self::$pages['health1']->getUuid(), $resultIds),
            \array_search(self::$pages['entertainment1']->getUuid(), $resultIds),
            "'Movie Reviews' should come before 'Fitness Tips' in descending order"
        );
    }

    public function testSortByAuthoredAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'authored' => 'asc',
        ]);

        $this->assertCount(8, $result);

        // First should have oldest authored date among sulu-io pages (not parent page)
        $this->assertStringContainsString('Latest in Tech', $result[0]['title']);
        $this->assertSame(self::$pages['tech1']->getUuid(), $result[0]['id']);

        // Check that pages are in correct chronological order
        $resultIds = \array_map(fn ($page) => $page['id'], $result);

        // Verify the pages with known authored dates are in correct order
        $this->assertLessThan(
            \array_search(self::$pages['sports1']->getUuid(), $resultIds),
            \array_search(self::$pages['tech1']->getUuid(), $resultIds),
            "'Latest in Tech' should come before 'Football Season' in ascending authored order"
        );

        $this->assertLessThan(
            \array_search(self::$pages['multi_category_multi_tag']->getUuid(), $resultIds),
            \array_search(self::$pages['business_tech']->getUuid(), $resultIds),
            "'Zero Tech Investments' should come before 'Digital Lifestyle' in ascending authored order"
        );

        // Last should have newest authored date among sulu-io pages
        $this->assertSame(self::$pages['multi_category_multi_tag']->getUuid(), $result[7]['id']);
        $this->assertStringContainsString('Digital Lifestyle', $result[7]['title']);
    }

    public function testSortByAuthoredDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy([...$this->getDefaultFilters(), ...['locale' => 'en']], [
            'authored' => 'desc',
        ]);

        $this->assertCount(8, $result);

        // First should have newest authored date among sulu-io pages
        $this->assertStringContainsString('Digital Lifestyle', $result[0]['title']);
        $this->assertSame(self::$pages['multi_category_multi_tag']->getUuid(), $result[0]['id'], "First result should be 'Digital Lifestyle'");

        // Check that pages are in correct reverse chronological order
        $resultIds = \array_map(fn ($page) => $page['id'], $result);

        // Verify the pages with known authored dates are in correct order
        $this->assertLessThan(
            \array_search(self::$pages['business_tech']->getUuid(), $resultIds),
            \array_search(self::$pages['multi_category_multi_tag']->getUuid(), $resultIds),
            "'Digital Lifestyle' should come before 'Zero Tech Investments' in descending authored order"
        );
        $this->assertLessThan(
            \array_search(self::$pages['tech1']->getUuid(), $resultIds),
            \array_search(self::$pages['sports1']->getUuid(), $resultIds),
            "'Football Season' should come before 'Latest in Tech' in descending authored order"
        );

        // Last should have oldest authored date among sulu-io pages (not parent page)
        $this->assertStringContainsString('Latest in Tech', $result[7]['title']);
        $this->assertSame(self::$pages['tech1']->getUuid(), $result[7]['id']);
    }

    public function testFindFlatByTypesSingleTemplateFilter(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'types' => ['default'],
            ],
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'types' => ['default'],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify all default template pages from sulu-io are returned
        $expectedKeys = ['tech1', 'sports1', 'health1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the default template result");
        }

        // Verify sulu-io parent page is NOT returned (data source should not be in results)
        $this->assertNotContains(self::$parentPages['sulu-io'], $resultIds, 'Parent page of sulu-io should not be in the result');

        // Verify non-default template pages from sulu-io are not returned
        $nonDefaultTemplateKeys = ['business1', 'entertainment1', 'business_tech'];
        foreach ($nonDefaultTemplateKeys as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the default template result");
        }

        // Verify blog pages are not returned (different data source)
        $blogPages = ['tech2', 'sports2', 'health2', 'business2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($blogPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the default template result");
        }
    }

    public function testFindFlatByTypesMultipleTemplateFilter(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'types' => ['blog', 'landing_page'],
            ],
        ], []);

        $this->assertCount(2, $result); // Only sulu-io pages with blog/landing_page templates
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'types' => ['blog', 'landing_page'],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify blog template pages from sulu-io are returned
        $blogTemplateKeys = ['entertainment1'];
        foreach ($blogTemplateKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the blog template result");
        }

        // Verify landing_page template pages from sulu-io are returned
        $landingPageTemplateKeys = ['business_tech'];
        foreach ($landingPageTemplateKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the landing_page template result");
        }

        // Verify pages with other templates from sulu-io are not returned
        $otherTemplateKeys = ['tech1', 'sports1', 'health1', 'business1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($otherTemplateKeys as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the blog/landing_page template result");
        }

        // Verify blog pages are not returned (different data source)
        $blogPages = ['tech2', 'sports2', 'health2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($blogPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the blog/landing_page template result");
        }

        // Verify parent pages are not returned (they use default template)
        $this->assertNotContains(self::$parentPages['sulu-io'], $resultIds, 'Parent page of sulu-io should not be in the result');
        $this->assertNotContains(self::$parentPages['blog'], $resultIds, 'Parent page of blog should not be in the result');
    }

    public function testFindFlatByTemplateKeysFilter(): void
    {
        // Test filtering using templateKeys instead of types
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'types' => ['homepage'],
            ],
        ], []);

        $this->assertCount(1, $result); // Only sulu-io homepage
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'types' => ['homepage'],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify sulu-io homepage is returned
        $this->assertContains(self::$pages['business1']->getUuid(), $resultIds, "Page 'business1' should be in the homepage template result");

        // Verify blog homepage is not returned (different data source)
        $this->assertNotContains(self::$pages['entertainment_business']->getUuid(), $resultIds, "Page 'entertainment_business' should not be in the homepage template result");
    }

    public function testFindFlatByDataSourceFilter(): void
    {
        // Test filtering by sulu-io parent page (dataSource)
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => self::$parentPages['sulu-io'],
            ],
        ], []);

        $this->assertCount(8, $result); // 8 sulu-io pages (parent page excluded)
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'dataSource' => self::$parentPages['sulu-io'],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify sulu-io pages are returned
        $expectedSuluIoPages = ['tech1', 'sports1', 'health1', 'business1', 'entertainment1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($expectedSuluIoPages as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the sulu-io result");
        }

        // Verify sulu-io parent page is NOT returned (data source should not be in results)
        $this->assertNotContains(self::$parentPages['sulu-io'], $resultIds, 'Parent page of sulu-io should not be in the result');

        // Test filtering by blog parent page
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => self::$parentPages['blog'],
            ],
        ], []);

        $this->assertCount(7, $result); // 7 blog pages (parent page excluded)
        $this->assertSame(
            7,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'dataSource' => self::$parentPages['blog'],
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify blog pages are returned
        $expectedBlogPages = ['tech2', 'sports2', 'health2', 'business2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($expectedBlogPages as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the blog result");
        }

        // Verify blog parent page is NOT returned (data source should not be in results)
        $this->assertNotContains(self::$parentPages['blog'], $resultIds, 'Parent page of blog should not be in the result');
    }

    // Add tests for blog webspace
    public function testFindFlatByBlogWebspace(): void
    {
        // Test filtering by blog parent page to get all blog pages
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => self::$parentPages['blog'],
            ],
        ], ['title' => 'asc']);

        $this->assertCount(7, $result); // 7 blog pages (parent page excluded)
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => self::$parentPages['blog'],
            ],
        ]);
        $this->assertSame(7, $count);

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify blog pages are returned
        $expectedBlogPages = ['tech2', 'sports2', 'health2', 'business2', 'entertainment2', 'sports_health', 'entertainment_business'];
        foreach ($expectedBlogPages as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the blog result");
        }

        // Verify blog parent page is NOT returned (data source should not be in results)
        $this->assertNotContains(self::$parentPages['blog'], $resultIds, 'Parent page of blog should not be in the result');

        // Verify sulu-io pages are NOT returned
        $suluIoPages = ['tech1', 'sports1', 'health1', 'business1', 'entertainment1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($suluIoPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the blog result");
        }
    }

    public function testFindFlatByBlogWebspaceCategoryFilter(): void
    {
        // Test category filtering with blog webspace
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'dataSource' => self::$parentPages['blog'],
                'categories' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
            ],
        ], []);

        $this->assertCount(1, $result); // Only blog tech pages
        $this->assertSame(
            1,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'dataSource' => self::$parentPages['blog'],
                    'categories' => [self::$categories['tech']->getId()],
                    'categoryOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct blog tech pages are returned
        $expectedKeys = ['tech2'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the blog tech result");
        }

        // Verify sulu-io tech pages are not returned
        $suluIoTechPages = ['tech1', 'tech_health', 'business_tech', 'multi_category_multi_tag'];
        foreach ($suluIoTechPages as $key) {
            $this->assertNotContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should not be in the blog tech result");
        }
    }

    /**
     * @param PageData $data
     * @param string[] $additionalLocales
     */
    private static function createPage(
        string $webspaceKey,
        string $parentId,
        array $data = [],
        array $additionalLocales = [],
    ): PageInterface {
        $data = \array_merge([
            'title' => 'Example Page',
            'url' => 'example-page-' . \uniqid(),
            'template' => 'default',
            'locale' => 'en',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        $envelope = $messageBus->dispatch(new Envelope(new CreatePageMessage(webspaceKey: $webspaceKey, parentId: $parentId, data: $data), [new EnableFlushStamp()]));
        /** @var HandledStamp[] $handledStamps */
        $handledStamps = $envelope->all(HandledStamp::class);

        /** @var PageInterface $page */
        $page = $handledStamps[0]->getResult();
        $messageBus->dispatch(
            new Envelope(
                new ApplyWorkflowTransitionPageMessage(
                    identifier: ['uuid' => $page->getUuid()],
                    locale: $data['locale'],
                    transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                ),
                [new EnableFlushStamp()],
            ),
        );

        foreach ($additionalLocales as $locale) {
            $messageBus->dispatch(new Envelope(new ModifyPageMessage(['uuid' => $page->getUuid()], \array_merge($data, ['locale' => $locale])), [new EnableFlushStamp()]));
            $messageBus->dispatch(
                new Envelope(
                    new ApplyWorkflowTransitionPageMessage(
                        identifier: ['uuid' => $page->getUuid()],
                        locale: $locale,
                        transitionName: WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH,
                    ),
                    [new EnableFlushStamp()],
                ),
            );
        }

        return $page;
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
            'dataSource' => self::$parentPages['sulu-io'],
            'limit' => null,
            'page' => 1,
            'maxPerPage' => null,
            'includeSubFolders' => false,
            'excludeDuplicates' => false,
        ];
    }

    public function testFindFlatByCombinedFilters(): void
    {
        // Test combination of template, and category filters
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            ...[
                'locale' => 'en',
                'types' => ['default'],
                'categories' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
            ],
        ], []);

        $this->assertCount(3, $result); // Only sulu-io default template tech pages
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                ...$this->getDefaultFilters(),
                ...[
                    'locale' => 'en',
                    'types' => ['default'],
                    'categories' => [self::$categories['tech']->getId()],
                    'categoryOperator' => 'OR',
                ],
            ]),
        );

        $resultIds = \array_map(
            fn ($page) => $page['id'],
            $result,
        );

        // Verify correct sulu-io pages are returned
        $expectedKeys = ['tech1', 'tech_health', 'multi_category_multi_tag'];
        foreach ($expectedKeys as $key) {
            $this->assertContains(self::$pages[$key]->getUuid(), $resultIds, "Page '$key' should be in the combined filter result");
        }

        // Verify blog tech pages are not returned (different data source)
        $this->assertNotContains(self::$pages['tech2']->getUuid(), $resultIds, "Page 'tech2' should not be in the combined filter result");

        // Verify business_tech is not returned (uses landing_page template, not default)
        $this->assertNotContains(self::$pages['business_tech']->getUuid(), $resultIds, "Page 'business_tech' should not be in the combined filter result (landing_page template)");
    }
}
