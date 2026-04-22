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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\PageTreeArticleSmartContentProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Tests\Traits\CreatePageTrait;
use Sulu\Route\Domain\Model\Route;
use Sulu\Route\Domain\Repository\RouteRepositoryInterface;
use Sulu\Route\Domain\Value\RequestAttributeEnum;

/**
 * @phpstan-import-type SmartContentBaseFilters from SmartContentProviderInterface
 */
class PageTreeArticleSmartContentProviderTest extends SuluTestCase
{
    use CreateArticleTrait;
    use CreatePageTrait;

    private readonly SmartContentProviderInterface $smartContentProvider;

    /**
     * @var array<string, ArticleInterface>
     */
    private static array $articles = [];

    /**
     * @var array<string, Page>
     */
    private static array $pages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->smartContentProvider = $this->getContainer()->get('sulu_article.page_tree_article_smart_content_provider');
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::purgeDatabase();
        self::bootKernel();

        $container = self::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $routeRepository = $container->get('sulu_route.route_repository');

        $requestContext = $container->get('router.request_context');
        $requestContext->setParameter(RequestAttributeEnum::WEBSPACE->value, 'sulu-io');

        // Create parent pages
        self::$pages['blog'] = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Blog',
                    'url' => '/blog',
                    'template' => 'default',
                ],
            ],
        ]);

        self::$pages['news'] = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'News',
                    'url' => '/news',
                    'template' => 'default',
                ],
            ],
        ]);

        self::$pages['products'] = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Products',
                    'url' => '/products',
                    'template' => 'default',
                ],
            ],
        ]);

        // Create child pages for testing includeSubFolders
        self::$pages['blog_tech'] = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'Tech Blog',
                    'url' => '/blog/tech',
                    'template' => 'default',
                    'parentId' => self::$pages['blog']->getUuid(),
                ],
            ],
        ]);

        self::$pages['blog_tech_ai'] = self::createPage([
            'en' => [
                'live' => [
                    'title' => 'AI Tech Blog',
                    'url' => '/blog/tech/ai',
                    'template' => 'default',
                    'parentId' => self::$pages['blog_tech']->getUuid(),
                ],
            ],
        ]);

        // Create articles
        self::$articles['blog_article_1'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'First Blog Post',
                    'url' => '/blog/first-blog-post',
                    'template' => 'article',
                ],
            ],
        ]);

        self::$articles['blog_article_2'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Second Blog Post',
                    'url' => '/blog/second-blog-post',
                    'template' => 'article',
                ],
            ],
        ]);

        self::$articles['news_article_1'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Breaking News',
                    'url' => '/news/breaking-news',
                    'template' => 'article',
                ],
            ],
        ]);

        self::$articles['news_article_2'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Latest Updates',
                    'url' => '/news/latest-updates',
                    'template' => 'article',
                ],
            ],
        ]);

        self::$articles['standalone_article'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Standalone Article',
                    'url' => '/standalone-article',
                    'template' => 'article',
                ],
            ],
        ]);

        // Create articles for child pages (to test includeSubFolders)
        self::$articles['tech_article_1'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Tech Article',
                    'url' => '/blog/tech/tech-article',
                    'template' => 'article',
                ],
            ],
        ]);

        self::$articles['ai_article_1'] = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'AI Article',
                    'url' => '/blog/tech/ai/ai-article',
                    'template' => 'article',
                ],
            ],
        ]);

        // Link article routes to page routes
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['blog_article_1'], self::$pages['blog']);
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['blog_article_2'], self::$pages['blog']);
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['news_article_1'], self::$pages['news']);
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['news_article_2'], self::$pages['news']);
        // Link child page articles
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['tech_article_1'], self::$pages['blog_tech']);
        self::linkArticleToPage($entityManager, $routeRepository, self::$articles['ai_article_1'], self::$pages['blog_tech_ai']);

        $entityManager->flush();
    }

    private static function linkArticleToPage(
        EntityManagerInterface $entityManager,
        RouteRepositoryInterface $routeRepository,
        ArticleInterface $article,
        Page $page
    ): void {
        /** @var Route|null $articleRoute */
        $articleRoute = $routeRepository->findOneBy([
            'resourceKey' => ArticleInterface::RESOURCE_KEY,
            'resourceId' => $article->getUuid(),
            'locale' => 'en',
        ]);

        /** @var Route|null $pageRoute */
        $pageRoute = $routeRepository->findOneBy([
            'resourceKey' => 'pages',
            'resourceId' => $page->getUuid(),
            'locale' => 'en',
        ]);

        if ($articleRoute && $pageRoute) {
            $articleRoute->setParentRoute($pageRoute);
        }
    }

    public function testGetType(): void
    {
        $this->assertSame(PageTreeArticleSmartContentProvider::PROVIDER_TYPE, $this->smartContentProvider->getType());
        $this->assertSame('articles_page_tree', $this->smartContentProvider->getType());
    }

    public function testGetConfiguration(): void
    {
        $config = $this->smartContentProvider->getConfiguration();

        $this->assertTrue($config->hasDatasource(), 'Configuration should have datasource enabled');
        $this->assertTrue($config->hasTags(), 'Configuration should have tags enabled');
        $this->assertTrue($config->hasCategories(), 'Configuration should have categories enabled');
    }

    public function testFindFlatByWithBlogDataSource(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
        ], []);

        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($article) => $article['id'], $result);

        $this->assertContains(self::$articles['blog_article_1']->getUuid(), $resultIds);
        $this->assertContains(self::$articles['blog_article_2']->getUuid(), $resultIds);
        $this->assertNotContains(self::$articles['news_article_1']->getUuid(), $resultIds);
        $this->assertNotContains(self::$articles['standalone_article']->getUuid(), $resultIds);
    }

    public function testFindFlatByWithNewsDataSource(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['news']->getUuid(),
        ], []);

        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($article) => $article['id'], $result);

        $this->assertContains(self::$articles['news_article_1']->getUuid(), $resultIds);
        $this->assertContains(self::$articles['news_article_2']->getUuid(), $resultIds);
        $this->assertNotContains(self::$articles['blog_article_1']->getUuid(), $resultIds);
    }

    public function testFindFlatByWithNoMatchingDataSource(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['products']->getUuid(),
        ], []);

        $this->assertCount(0, $result);
    }

    public function testFindFlatByWithoutDataSource(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => null,
        ], []);

        // Without dataSource filter, all published articles should be returned
        // blog_article_1, blog_article_2, news_article_1, news_article_2, standalone_article, tech_article_1, ai_article_1
        $this->assertCount(7, $result);
    }

    public function testCountByWithBlogDataSource(): void
    {
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
        ]);

        $this->assertSame(2, $count);
    }

    public function testCountByWithNewsDataSource(): void
    {
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['news']->getUuid(),
        ]);

        $this->assertSame(2, $count);
    }

    public function testCountByWithNoMatchingDataSource(): void
    {
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['products']->getUuid(),
        ]);

        $this->assertSame(0, $count);
    }

    public function testFindFlatByWithPagination(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'limit' => 1,
            'offset' => 0,
        ], ['title' => 'asc']);

        $this->assertCount(1, $result);
        $this->assertSame('First Blog Post', $result[0]['title']);

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'limit' => 1,
            'offset' => 1,
        ], ['title' => 'asc']);

        $this->assertCount(1, $result);
        $this->assertSame('Second Blog Post', $result[0]['title']);
    }

    public function testFindFlatByWithSorting(): void
    {
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
        ], ['title' => 'asc']);

        $this->assertCount(2, $result);
        $this->assertSame('First Blog Post', $result[0]['title']);
        $this->assertSame('Second Blog Post', $result[1]['title']);

        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
        ], ['title' => 'desc']);

        $this->assertCount(2, $result);
        $this->assertSame('Second Blog Post', $result[0]['title']);
        $this->assertSame('First Blog Post', $result[1]['title']);
    }

    public function testFindFlatByWithIncludeSubFoldersDisabled(): void
    {
        // Without includeSubFolders, should only return articles directly linked to the blog page
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'includeSubFolders' => false,
        ], []);

        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($article) => $article['id'], $result);

        // Only direct blog articles should be included
        $this->assertContains(self::$articles['blog_article_1']->getUuid(), $resultIds);
        $this->assertContains(self::$articles['blog_article_2']->getUuid(), $resultIds);
        // Articles from child pages should NOT be included
        $this->assertNotContains(self::$articles['tech_article_1']->getUuid(), $resultIds);
        $this->assertNotContains(self::$articles['ai_article_1']->getUuid(), $resultIds);
    }

    public function testFindFlatByWithIncludeSubFoldersEnabled(): void
    {
        // With includeSubFolders, should return articles from blog page AND all subpages
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'includeSubFolders' => true,
        ], []);

        $this->assertCount(4, $result);

        $resultIds = \array_map(fn ($article) => $article['id'], $result);

        // Direct blog articles should be included
        $this->assertContains(self::$articles['blog_article_1']->getUuid(), $resultIds);
        $this->assertContains(self::$articles['blog_article_2']->getUuid(), $resultIds);
        // Articles from child pages should also be included
        $this->assertContains(self::$articles['tech_article_1']->getUuid(), $resultIds);
        $this->assertContains(self::$articles['ai_article_1']->getUuid(), $resultIds);
        // News articles should NOT be included
        $this->assertNotContains(self::$articles['news_article_1']->getUuid(), $resultIds);
    }

    public function testFindFlatByWithIncludeSubFoldersFromChildPage(): void
    {
        // includeSubFolders from a child page (blog_tech) should include grandchild articles
        $result = $this->smartContentProvider->findFlatBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog_tech']->getUuid(),
            'includeSubFolders' => true,
        ], []);

        $this->assertCount(2, $result);

        $resultIds = \array_map(fn ($article) => $article['id'], $result);

        // Tech article (direct child of blog_tech) should be included
        $this->assertContains(self::$articles['tech_article_1']->getUuid(), $resultIds);
        // AI article (grandchild via blog_tech_ai) should be included
        $this->assertContains(self::$articles['ai_article_1']->getUuid(), $resultIds);
        // Blog articles (parent page) should NOT be included
        $this->assertNotContains(self::$articles['blog_article_1']->getUuid(), $resultIds);
    }

    public function testCountByWithIncludeSubFoldersEnabled(): void
    {
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'includeSubFolders' => true,
        ]);

        // Should count blog articles (2) + tech article (1) + AI article (1) = 4
        $this->assertSame(4, $count);
    }

    public function testCountByWithIncludeSubFoldersDisabled(): void
    {
        $count = $this->smartContentProvider->countBy([
            ...$this->getDefaultFilters(),
            'locale' => 'en',
            'dataSource' => self::$pages['blog']->getUuid(),
            'includeSubFolders' => false,
        ]);

        // Should only count direct blog articles (2)
        $this->assertSame(2, $count);
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
            'offset' => 0,
            'includeSubFolders' => false,
            'excludeDuplicates' => false,
        ];
    }
}
