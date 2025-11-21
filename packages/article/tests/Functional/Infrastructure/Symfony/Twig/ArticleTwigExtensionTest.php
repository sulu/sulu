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

use Sulu\Article\Infrastructure\Symfony\Twig\ArticleTwigExtension;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;

class ArticleTwigExtensionTest extends SuluTestCase
{
    use CreateMediaTrait;
    use CreateArticleTrait;

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
}
