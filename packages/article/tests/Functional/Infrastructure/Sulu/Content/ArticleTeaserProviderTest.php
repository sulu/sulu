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

use PHPUnit\Framework\Attributes\CoversClass;
use Sulu\Article\Infrastructure\Sulu\Content\ArticleTeaserProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\WebsiteTestCase;
use Sulu\Content\Tests\Functional\Traits\CreateMediaTrait;
use Symfony\Component\Routing\RequestContext;

#[CoversClass(ArticleTeaserProvider::class)]
class ArticleTeaserProviderTest extends WebsiteTestCase
{
    use CreateArticleTrait;
    use CreateMediaTrait;
    use SetGetPrivatePropertyTrait;

    private ArticleTeaserProvider $teaserProvider;

    public static function setUpBeforeClass(): void
    {
        static::purgeDatabase();
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->teaserProvider = self::getContainer()->get('sulu_article.article_teaser_provider');

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'blog');
    }

    public function testGetConfiguration(): void
    {
        $configuration = $this->teaserProvider->getConfiguration();

        $this->assertSame('articles', self::getPrivateProperty($configuration, 'resourceKey'));
        $this->assertSame('table', self::getPrivateProperty($configuration, 'listAdapter'));
        $this->assertSame(['title'], self::getPrivateProperty($configuration, 'displayProperties'));
        $this->assertNull(self::getPrivateProperty($configuration, 'view'));
        $this->assertNull(self::getPrivateProperty($configuration, 'resultToView'));
    }

    public function testFindReturnsEmptyArrayForEmptyIds(): void
    {
        $result = $this->teaserProvider->find([], 'en');

        $this->assertSame([], $result);
    }

    public function testFindReturnsTeasersWithExcerptData(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Test Article Title',
                    'template' => 'article',
                    'url' => '/test-article',
                    'mainWebspace' => 'blog',
                    'excerpt' => [
                        'title' => 'Excerpt Title',
                        'description' => 'Excerpt description text',
                        'more' => 'Read more',
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $teaser = $teasers[0];

        $this->assertSame($article->getUuid(), $teaser->getId());
        $this->assertSame('articles', $teaser->getType());
        $this->assertSame('en', $teaser->getLocale());
        $this->assertSame('Excerpt Title', $teaser->getTitle());
        $this->assertSame('Excerpt description text', $teaser->getDescription());
        $this->assertSame('Read more', $teaser->getMoreText());
        $this->assertNotNull($teaser->getUrl());
    }

    public function testFindReturnsEmptyTitleWhenNoExcerptTitle(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Article Without Excerpt Title',
                    'template' => 'article',
                    'url' => '/no-excerpt-title',
                    'mainWebspace' => 'blog',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('', $teasers[0]->getTitle());
    }

    public function testFindReturnsTeaserWithMediaId(): void
    {
        $collection = self::createCollection();
        $media = self::createMedia($collection, ['title' => 'Teaser Image']);
        self::getEntityManager()->flush();

        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Article With Excerpt Image',
                    'template' => 'article',
                    'url' => '/with-excerpt-image',
                    'mainWebspace' => 'blog',
                    'excerpt' => [
                        'title' => 'Excerpt With Image',
                        'image' => ['id' => $media->getId()],
                    ],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame($media->getId(), $teasers[0]->getMediaId());
    }

    public function testFindSkipsUnpublishedArticles(): void
    {
        $article = self::createArticle([
            'en' => [
                'draft' => [
                    'title' => 'Draft Only Article',
                    'template' => 'article',
                    'url' => '/draft-article',
                    'mainWebspace' => 'blog',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(0, $teasers);
    }

    public function testFindSkipsArticlesWithoutMatchingLocale(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'English Only Article',
                    'template' => 'article',
                    'url' => '/english-only',
                    'mainWebspace' => 'blog',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'de');

        $this->assertCount(0, $teasers);
    }

    public function testFindPreservesOrder(): void
    {
        $article1 = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'First Article',
                    'template' => 'article',
                    'url' => '/first',
                    'mainWebspace' => 'blog',
                    'excerpt' => ['title' => 'First'],
                ],
            ],
        ]);

        $article2 = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Second Article',
                    'template' => 'article',
                    'url' => '/second',
                    'mainWebspace' => 'blog',
                    'excerpt' => ['title' => 'Second'],
                ],
            ],
        ]);

        $article3 = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Third Article',
                    'template' => 'article',
                    'url' => '/third',
                    'mainWebspace' => 'blog',
                    'excerpt' => ['title' => 'Third'],
                ],
            ],
        ]);

        // Request in reverse order
        $teasers = $this->teaserProvider->find([
            $article3->getUuid(),
            $article1->getUuid(),
            $article2->getUuid(),
        ], 'en');

        $this->assertCount(3, $teasers);
        $this->assertSame($article3->getUuid(), $teasers[0]->getId());
        $this->assertSame($article1->getUuid(), $teasers[1]->getId());
        $this->assertSame($article2->getUuid(), $teasers[2]->getId());
    }

    public function testFindWithMultipleLocales(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'English Title',
                    'template' => 'article',
                    'url' => '/english',
                    'mainWebspace' => 'blog',
                    'excerpt' => ['title' => 'EN Excerpt'],
                ],
            ],
            'de' => [
                'live' => [
                    'title' => 'German Title',
                    'template' => 'article',
                    'url' => '/deutsch',
                    'mainWebspace' => 'blog',
                    'excerpt' => ['title' => 'DE Excerpt'],
                ],
            ],
        ]);

        $englishTeasers = $this->teaserProvider->find([$article->getUuid()], 'en');
        $germanTeasers = $this->teaserProvider->find([$article->getUuid()], 'de');

        $this->assertCount(1, $englishTeasers);
        $this->assertCount(1, $germanTeasers);
        $this->assertSame('EN Excerpt', $englishTeasers[0]->getTitle());
        $this->assertSame('DE Excerpt', $germanTeasers[0]->getTitle());
    }

    public function testFindUsesMainWebspaceWhenRequestWebspaceNotInAdditionalWebspaces(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Multi Webspace Article',
                    'template' => 'article',
                    'url' => '/multi-webspace',
                    'mainWebspace' => 'blog',
                    'additionalWebspaces' => ['sulu-io'],
                    'excerpt' => ['title' => 'Multi Webspace Excerpt'],
                ],
            ],
        ]);

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'blog');

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertStringContainsString('blog.io', $teasers[0]->getUrl());
    }

    public function testFindUsesRequestWebspaceWhenInAdditionalWebspaces(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Multi Webspace Article 2',
                    'template' => 'article',
                    'url' => '/multi-webspace-2',
                    'mainWebspace' => 'blog',
                    'additionalWebspaces' => ['sulu-io'],
                    'excerpt' => ['title' => 'Multi Webspace Excerpt 2'],
                ],
            ],
        ]);

        /** @var RequestContext $requestContext */
        $requestContext = self::getContainer()->get('router')->getContext();
        $requestContext->setParameter('webspace', 'sulu-io');

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertStringContainsString('sulu.io', $teasers[0]->getUrl());
    }

    public function testFindUsesTaggedDescriptionWhenExcerptEmpty(): void
    {
        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Article With Tagged Description',
                    'template' => 'teaser-tagged',
                    'url' => '/tagged-description',
                    'mainWebspace' => 'blog',
                    'teaser_description' => 'Description from tagged property',
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('Description from tagged property', $teasers[0]->getDescription());
    }

    public function testFindUsesTaggedMediaWhenExcerptEmpty(): void
    {
        $collection = self::createCollection();
        $media1 = self::createMedia($collection, ['title' => 'Tagged Teaser Image 1']);
        $media2 = self::createMedia($collection, ['title' => 'Tagged Teaser Image 2']);
        self::getEntityManager()->flush();

        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Article With Tagged Media',
                    'template' => 'teaser-tagged',
                    'url' => '/tagged-media',
                    'mainWebspace' => 'blog',
                    'teaser_image' => ['ids' => [$media1->getId(), $media2->getId()], 'displayOption' => 'left'],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame($media1->getId(), $teasers[0]->getMediaId());
    }

    public function testFindPrefersExcerptOverTaggedProperty(): void
    {
        $collection = self::createCollection();
        $excerptMedia = self::createMedia($collection, ['title' => 'Excerpt Image']);
        $taggedMedia = self::createMedia($collection, ['title' => 'Tagged Image']);
        self::getEntityManager()->flush();

        $article = self::createArticle([
            'en' => [
                'live' => [
                    'title' => 'Article With Both Excerpt And Tagged',
                    'template' => 'teaser-tagged',
                    'url' => '/excerpt-over-tagged',
                    'mainWebspace' => 'blog',
                    'excerpt' => [
                        'description' => 'Excerpt description',
                        'image' => ['id' => $excerptMedia->getId()],
                    ],
                    'teaser_description' => 'Tagged description',
                    'teaser_image' => ['ids' => [$taggedMedia->getId()], 'displayOption' => 'left'],
                ],
            ],
        ]);

        $teasers = $this->teaserProvider->find([$article->getUuid()], 'en');

        $this->assertCount(1, $teasers);
        $this->assertSame('Excerpt description', $teasers[0]->getDescription());
        $this->assertSame($excerptMedia->getId(), $teasers[0]->getMediaId());
    }
}
