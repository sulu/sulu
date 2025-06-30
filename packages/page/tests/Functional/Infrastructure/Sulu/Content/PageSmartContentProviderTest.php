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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\WorkflowInterface;
use Sulu\Content\Tests\Traits\CreateCategoryTrait;
use Sulu\Content\Tests\Traits\CreateTagTrait;
use Sulu\Messenger\Infrastructure\Symfony\Messenger\FlushMiddleware\EnableFlushStamp;
use Sulu\Page\Application\Message\ApplyWorkflowTransitionPageMessage;
use Sulu\Page\Application\Message\CreatePageMessage;
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
 *     webspaceKey?: string,
 *     author?: int|null,
 *     authored?: string|null,
 * }
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
     * @var array<string, mixed>
     */
    private static array $categories = [];

    /**
     * @var array<string, string>
     */
    private static array $tags = [];

    /**
     * @var array<string, string>
     */
    private static array $webspaces = ['sulu_io', 'blog'];

    /**
     * @var array<string, string>
     */
    private static array $templates = ['default', 'homepage', 'landing_page', 'blog'];

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

            $parentPage = self::createPage($webspaceKey, CreatePageMessageHandler::HOMEPAGE_PARENT_ID, $parentData);
            self::$parentPages[$webspaceKey] = $parentPage->getUuid();
        }

        // Create pages with various combinations of categories, tags, templates, and webspaces
        // Technology pages
        self::$pages['tech1'] = self::createPage(
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
            'sulu_io',
            self::$parentPages['sulu_io'],
            [
                'title' => 'Tech Investments',
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
            'sulu_io',
            self::$parentPages['sulu_io'],
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
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], []);

        $this->assertCount(17, $result); // 15 pages + 2 parent pages
        $count = $this->smartContentProvider->countBy(['locale' => 'en']);
        $this->assertSame(17, $count);
    }

    public function testFindFlatByCategoryFiltersSingleCategoryOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [self::$categories['tech']->getId()],
            'categoryOperator' => 'OR',
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
            ]),
        );
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
            'categoryOperator' => 'OR',
        ], ['title' => 'asc']);

        // Should include tech1, tech2, health1, health2, tech_health, sports_health, business_tech, multi_category_multi_tag
        $this->assertCount(8, $result);
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                'categoryOperator' => 'OR',
            ]),
        );
    }

    public function testFindFlatByCategoryFiltersSingleCategoryAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [self::$categories['health']->getId()],
            'categoryOperator' => 'AND',
        ], []);

        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [self::$categories['health']->getId()],
                'categoryOperator' => 'AND',
            ]),
        );
    }

    public function testFindFlatByCategoryFiltersMultipleCategoriesAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
            'categoryOperator' => 'AND',
        ], []);

        // Should include tech_health and multi_category_multi_tag
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [self::$categories['tech']->getId(), self::$categories['health']->getId()],
                'categoryOperator' => 'AND',
            ]),
        );
    }

    public function testFindFlatByTagFiltersSingleTagOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => [self::$tags['mobile']],
            'tagOperator' => 'OR',
        ], []);

        // Should include tech1, tech_health, multi_category_multi_tag
        $this->assertCount(3, $result);
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => [self::$tags['mobile']],
                'tagOperator' => 'OR',
            ]),
        );
    }

    public function testFindFlatByTagFiltersMultipleTagsOR(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => [self::$tags['mobile'], self::$tags['cloud']],
            'tagOperator' => 'OR',
        ], ['title' => 'asc']);

        // Should include tech1, tech2, tech_health, business_tech, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => [self::$tags['mobile'], self::$tags['cloud']],
                'tagOperator' => 'OR',
            ]),
        );
    }

    public function testFindFlatByTagFiltersSingleTagAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => [self::$tags['fitness']],
            'tagOperator' => 'AND',
        ], []);

        // Should include health1, health2, tech_health, sports_health, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => [self::$tags['fitness']],
                'tagOperator' => 'AND',
            ]),
        );
    }

    public function testFindFlatByTagFiltersMultipleTagsAND(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'tagNames' => [self::$tags['mobile'], self::$tags['fitness']],
            'tagOperator' => 'AND',
        ], []);

        // Should include tech_health, multi_category_multi_tag
        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'tagNames' => [self::$tags['mobile'], self::$tags['fitness']],
                'tagOperator' => 'AND',
            ]),
        );
    }

    public function testFindFlatByCategoryAndTagFilters(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'categoryIds' => [self::$categories['health']->getId()],
            'tagNames' => [self::$tags['fitness']],
        ], []);

        // Should include health1, health2, tech_health, sports_health, multi_category_multi_tag
        $this->assertCount(5, $result);
        $this->assertSame(
            5,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'categoryIds' => [self::$categories['health']->getId()],
                'tagNames' => [self::$tags['fitness']],
            ]),
        );
    }

    public function testFindFlatByLimitAndPageFirst(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'limit' => 5,
            'page' => 1,
        ], [
            'title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            17, // 15 pages + 2 parent pages
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'limit' => 5,
                'page' => 1,
            ]),
        );
    }

    public function testFindFlatByLimitAndPageSecond(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'limit' => 5,
            'page' => 2,
        ], [
            'title' => 'asc',
        ]);

        $this->assertCount(5, $result);
        $this->assertSame(
            17, // 15 pages + 2 parent pages
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'limit' => 5,
                'page' => 2,
            ]),
        );
    }

    public function testSortByTitleAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'title' => 'asc',
        ]);

        $this->assertCount(17, $result);
        // Check if first article is alphabetically first
        $this->assertStringContainsString('Cloud Computing', $result[0]['title']);
        // Check if last article is alphabetically last
        $this->assertStringContainsString('Tennis Championship', $result[16]['title']);
    }

    public function testSortByTitleDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'title' => 'desc',
        ]);

        $this->assertCount(17, $result);
        // Check if first article is alphabetically last
        $this->assertStringContainsString('Tennis Championship', $result[0]['title']);
        // Check if last article is alphabetically first
        $this->assertStringContainsString('Cloud Computing', $result[16]['title']);
    }

    public function testSortByAuthoredAsc(): void
    {
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'authored' => 'asc',
        ]);

        $this->assertCount(17, $result);
        $firstPage = \reset($result);
        $lastPage = \end($result);

        // First should have oldest authored date
        $this->assertStringContainsString('Parent Page blog', $firstPage['title']);
        // Last should have newest authored date
        $this->assertStringContainsString('Digital Lifestyle', $lastPage['title']);
    }

    public function testSortByAuthoredDesc(): void
    {
        $result = $this->smartContentProvider->findFlatBy(['locale' => 'en'], [
            'authored' => 'desc',
        ]);

        $this->assertCount(17, $result);
        $firstPage = \reset($result);
        $lastPage = \end($result);

        // First should have oldest authored date
        $this->assertStringContainsString('Digital Lifestyle', $firstPage['title']);
        // Last should have newest authored date
        $this->assertStringContainsString('Parent Page blog', $lastPage['title']);
    }

    public function testFindFlatByWebspaceKeyFilter(): void
    {
        // Test filtering by sulu_io webspace
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'webspaceKey' => 'sulu_io',
        ], []);

        // Count pages in sulu_io webspace (8 regular pages + 1 parent page)
        $this->assertCount(9, $result);
        $this->assertSame(
            9,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'webspaceKey' => 'sulu_io',
            ]),
        );

        // Test filtering by blog webspace
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'webspaceKey' => 'blog',
        ], []);

        // Count pages in blog webspace (7 regular pages + 1 parent page)
        $this->assertCount(8, $result);
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'webspaceKey' => 'blog',
            ]),
        );
    }

    public function testFindFlatByTypesSingleTemplateFilter(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'types' => ['default'],
        ], []);

        $this->assertCount(8, $result); // 6 regular pages + 2 parent pages
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'types' => ['default'],
            ]),
        );
    }

    public function testFindFlatByTypesMultipleTemplateFilter(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'types' => ['blog', 'landing_page'],
        ], []);

        $this->assertCount(7, $result);
        $this->assertSame(
            7,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'types' => ['blog', 'landing_page'],
            ]),
        );
    }

    public function testFindFlatByTemplateKeysFilter(): void
    {
        // Test filtering using templateKeys instead of types
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'templateKeys' => ['homepage'],
        ], []);

        $this->assertCount(2, $result);
        $this->assertSame(
            2,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'templateKeys' => ['homepage'],
            ]),
        );
    }

    public function testFindFlatByDataSourceFilter(): void
    {
        // Test filtering by parent page (dataSource)
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'dataSource' => self::$parentPages['sulu_io'],
        ], []);

        $this->assertCount(8, $result);
        $this->assertSame(
            8,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'dataSource' => self::$parentPages['sulu_io'],
            ]),
        );

        // Test filtering by the other parent page
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'dataSource' => self::$parentPages['blog'],
        ], []);

        $this->assertCount(7, $result);
        $this->assertSame(
            7,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'dataSource' => self::$parentPages['blog'],
            ]),
        );
    }

    public function testFindFlatByCombinedFilters(): void
    {
        // Test combination of webspaceKey, template, and category filters
        $result = $this->smartContentProvider->findFlatBy([
            'locale' => 'en',
            'webspaceKey' => 'sulu_io',
            'types' => ['default'],
            'categoryIds' => [self::$categories['tech']->getId()],
            'categoryOperator' => 'OR',
        ], []);

        $this->assertCount(3, $result); // tech1, tech_health, multi_category_multi_tag
        $this->assertSame(
            3,
            $this->smartContentProvider->countBy([
                'locale' => 'en',
                'webspaceKey' => 'sulu_io',
                'types' => ['default'],
                'categoryIds' => [self::$categories['tech']->getId()],
                'categoryOperator' => 'OR',
            ]),
        );
    }

    /**
     * @param PageData $data
     */
    private static function createPage(
        string $webspaceKey,
        string $parentId,
        array $data = [],
    ): PageInterface {
        $data = \array_merge([
            'title' => 'Example Page',
            'url' => 'example-page-' . \uniqid(),
            'template' => 'default',
            'locale' => 'en',
        ], $data);

        $messageBus = self::getContainer()->get('sulu_message_bus');

        /** @var PageInterface $page */
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

        return $page;
    }
}
