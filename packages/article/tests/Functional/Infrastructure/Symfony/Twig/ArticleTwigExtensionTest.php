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

namespace Sulu\Article\Tests\Functional\Infrastructure\Symfony\Twig;

use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Symfony\Twig\ArticleTwigExtension;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Webspace;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Symfony\Component\HttpFoundation\Request;

class ArticleTwigExtensionTest extends SuluTestCase
{
    use CreateMediaTrait;
    use CreateArticleTrait;
    use ProphecyTrait;

    private ArticleTwigExtension $articleTwigExtension;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->articleTwigExtension = self::getContainer()->get('sulu_article.article_twig_extension');
    }

    public function testLoadArticleWithoutProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Test Article',
                    'url' => '/test-article',
                    'description' => 'This is a test article description',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);

        $result = $this->articleTwigExtension->loadArticle($article->getUuid(), [], 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);

        /** @var array<string, mixed> $content */
        $content = $result['content'];
        $this->assertEmpty($content);
    }

    public function testLoadArticleWithProperties(): void
    {
        $collection = self::createCollection(['title' => 'Test Collection', 'locale' => 'en']);
        $media = self::createMedia($collection, ['title' => 'Test Image', 'locale' => 'en']);

        self::getEntityManager()->flush();

        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Test Article with Properties',
                    'url' => '/test-article-props',
                    'description' => 'Description for properties test',
                    'image' => [
                        'id' => $media->getId(),
                    ],
                ],
            ],
        ]);

        $properties = [
            'title' => 'title',
            'description' => 'description',
        ];

        $result = $this->articleTwigExtension->loadArticle($article->getUuid(), $properties, 'en');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('Test Article with Properties', $result['title']);
        $this->assertArrayHasKey('description', $result);
        $this->assertSame('Description for properties test', $result['description']);

        if (isset($result['content'])) {
            $this->assertEmpty($result['content']);
        }
        $this->assertArrayNotHasKey('image', $result);
    }

    public function testLoadArticleReturnsNullWhenArticleNotFound(): void
    {
        $result = $this->articleTwigExtension->loadArticle('nonexistent-uuid', [], 'en');

        $this->assertNull($result);
    }

    public function testLoadArticleWithShadowLocale(): void
    {
        $article = static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'English Article',
                    'url' => '/english-article',
                    'description' => 'English description',
                ],
            ],
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'German Shadow Article',
                    'url' => '/deutscher-artikel',
                    'shadowOn' => true,
                    'shadowLocale' => 'en',
                ],
            ],
        ]);

        $result = $this->articleTwigExtension->loadArticle($article->getUuid(), ['title' => 'title'], 'de');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertSame('English Article', $result['title']);
    }

    public function testLoadRecentArticlesNewestAuthoredFirst(): void
    {
        $this->createAuthoredArticle('Old', '2024-01-01T10:00:00');
        $this->createAuthoredArticle('Newest', '2026-01-01T10:00:00');
        $this->createAuthoredArticle('Middle', '2025-01-01T10:00:00');

        $result = $this->articleTwigExtension->loadRecentArticles(2, ['title' => 'title'], null, 'en');

        $this->assertSame(['Newest', 'Middle'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesWithoutCurrentArticle(): void
    {
        $this->createAuthoredArticle('Old', '2024-01-01T10:00:00');
        $current = $this->createAuthoredArticle('Newest', '2026-01-01T10:00:00');
        $this->createAuthoredArticle('Middle', '2025-01-01T10:00:00');

        $request = new Request();
        $request->attributes->set('object', $this->getLiveDimensionContent($current, 'en'));
        self::getContainer()->get('request_stack')->push($request);

        try {
            $result = $this->articleTwigExtension->loadRecentArticles(2, ['title' => 'title'], null, 'en');
        } finally {
            self::getContainer()->get('request_stack')->pop();
        }

        $this->assertSame(['Middle', 'Old'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesByTemplateKeys(): void
    {
        $this->createAuthoredArticle('Article', '2026-01-01T10:00:00');
        $this->createAuthoredArticle('Blog', '2025-01-01T10:00:00', 'blog');

        $result = $this->articleTwigExtension->loadRecentArticles(5, ['title' => 'title'], ['blog'], 'en');

        $this->assertSame(['Blog'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesOnlyInLocale(): void
    {
        $this->createAuthoredArticle('English', '2026-01-01T10:00:00');
        static::createArticle([
            'de' => [
                'live' => [
                    'template' => 'article',
                    'title' => 'Deutsch',
                    'url' => '/deutsch',
                    'authored' => '2025-01-01T10:00:00',
                ],
            ],
        ]);

        $result = $this->articleTwigExtension->loadRecentArticles(5, ['title' => 'title'], null, 'de');

        $this->assertSame(['Deutsch'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesWithoutDrafts(): void
    {
        $this->createAuthoredArticle('Live', '2025-01-01T10:00:00');
        static::createArticle([
            'en' => [
                'draft' => [
                    'template' => 'article',
                    'title' => 'Draft',
                    'url' => '/draft',
                    'authored' => '2026-01-01T10:00:00',
                ],
            ],
        ]);

        $result = $this->articleTwigExtension->loadRecentArticles(5, ['title' => 'title'], null, 'en');

        $this->assertSame(['Live'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesWithLimitBelowOne(): void
    {
        $this->createAuthoredArticle('Article', '2026-01-01T10:00:00');

        $this->assertCount(0, $this->articleTwigExtension->loadRecentArticles(0, ['title' => 'title'], null, 'en'));
    }

    public function testLoadRecentArticlesInCurrentWebspace(): void
    {
        $this->createArticleInWebspace('Sulu', '2026-01-01T10:00:00', 'sulu-io');
        $this->createArticleInWebspace('Blog', '2025-01-01T10:00:00', 'blog');

        $result = $this->createTwigExtensionInWebspace('blog')->loadRecentArticles(5, ['title' => 'title'], null, 'en');

        $this->assertSame(['Blog'], \array_column($result, 'title'));
    }

    public function testLoadRecentArticlesIgnoringWebspaces(): void
    {
        $this->createArticleInWebspace('Sulu', '2026-01-01T10:00:00', 'sulu-io');
        $this->createArticleInWebspace('Blog', '2025-01-01T10:00:00', 'blog');

        $result = $this->createTwigExtensionInWebspace('blog')->loadRecentArticles(5, ['title' => 'title'], null, 'en', true);

        $this->assertSame(['Sulu', 'Blog'], \array_column($result, 'title'));
    }

    public function testConstructWithoutRequestStack(): void
    {
        $deprecations = [];
        \set_error_handler(static function(int $errorNumber, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, \E_USER_DEPRECATED);

        try {
            new ArticleTwigExtension(
                self::getContainer()->get('sulu_article.article_repository'),
                self::getContainer()->get('sulu_content.content_aggregator'),
                self::getContainer()->get('sulu_core.webspace.request_analyzer'),
                self::getContainer()->get('sulu_http_cache.reference_store'),
                self::getContainer()->get('sulu_content.content_resolver'),
            );
        } finally {
            \restore_error_handler();
        }

        $this->assertSame(
            ['Since sulu/sulu 3.0.12: Instantiating ArticleTwigExtension without the $requestStack argument is deprecated.'],
            $deprecations
        );
    }

    private function createTwigExtensionInWebspace(string $webspaceKey): ArticleTwigExtension
    {
        $webspace = new Webspace();
        $webspace->setKey($webspaceKey);

        $requestAnalyzer = $this->prophesize(RequestAnalyzerInterface::class);
        $requestAnalyzer->getWebspace()->willReturn($webspace);

        return new ArticleTwigExtension(
            self::getContainer()->get('sulu_article.article_repository'),
            self::getContainer()->get('sulu_content.content_aggregator'),
            $requestAnalyzer->reveal(),
            self::getContainer()->get('sulu_http_cache.reference_store'),
            self::getContainer()->get('sulu_content.content_resolver'),
            self::getContainer()->get('request_stack'),
        );
    }

    private function createArticleInWebspace(string $title, string $authored, string $mainWebspace): ArticleInterface
    {
        return static::createArticle([
            'en' => [
                'live' => [
                    'template' => 'article',
                    'title' => $title,
                    'url' => '/' . \strtolower($title),
                    'authored' => $authored,
                    'customizeWebspaceSettings' => true,
                    'mainWebspace' => $mainWebspace,
                ],
            ],
        ]);
    }

    private function createAuthoredArticle(string $title, string $authored, string $template = 'article'): ArticleInterface
    {
        return static::createArticle([
            'en' => [
                'live' => [
                    'template' => $template,
                    'title' => $title,
                    'url' => '/' . \strtolower($title),
                    'authored' => $authored,
                ],
            ],
        ]);
    }

    private function getLiveDimensionContent(ArticleInterface $article, string $locale): ArticleDimensionContentInterface
    {
        foreach ($article->getDimensionContents() as $dimensionContent) {
            if ($locale === $dimensionContent->getLocale() && DimensionContentInterface::STAGE_LIVE === $dimensionContent->getStage()) {
                return $dimensionContent;
            }
        }

        throw new \RuntimeException('No live dimension content for locale ' . $locale);
    }
}
