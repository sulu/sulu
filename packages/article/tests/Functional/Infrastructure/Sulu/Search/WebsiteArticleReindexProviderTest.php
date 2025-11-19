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
use Sulu\Article\Infrastructure\Sulu\Search\WebsiteArticleReindexProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
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
 */
class WebsiteArticleReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;
    use CreateArticleTrait;

    private EntityManagerInterface $entityManager;
    private WebsiteArticleReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new WebsiteArticleReindexProvider($this->entityManager);
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

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $article2->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article1->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article1->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io', 'blog'],
                'title' => 'Article One EN',
                'url' => '/test-article',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable('1995-11-29 12:00:00'))->format('c'),
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::de',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'de',
                'webspaces' => ['blog'],
                'title' => 'Article TWO DE',
                'url' => '/test-article-two',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO EN',
                'url' => '/test-article-two-en',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
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

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $article2->getUuid(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ]);

        $config = ReindexConfig::create()->withIndex('website');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article1->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article1->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article One EN',
                'url' => '/test-article',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable('1995-11-29 12:00:00'))->format('c'),
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::de',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'de',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO DE',
                'url' => '/test-article-two',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'locale' => 'en',
                'webspaces' => ['sulu-io'],
                'title' => 'Article TWO EN',
                'url' => '/test-article-two-en',
                'content' => [],
                'mediaId' => '',
                'authoredAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
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
            ArticleInterface::RESOURCE_KEY . '::' . $article1->getUuid() . '::en',
            ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::de',
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
}
