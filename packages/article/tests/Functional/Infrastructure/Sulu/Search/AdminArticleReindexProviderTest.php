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
use Sulu\Article\Infrastructure\Sulu\Search\AdminArticleReindexProvider;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

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
class AdminArticleReindexProviderTest extends SuluTestCase
{
    use SetGetPrivatePropertyTrait;
    use CreateArticleTrait;

    private EntityManagerInterface $entityManager;
    private AdminArticleReindexProvider $provider;

    protected function setUp(): void
    {
        $this->entityManager = $this->getEntityManager();
        $this->provider = new AdminArticleReindexProvider($this->entityManager);
        $this->purgeDatabase();
    }

    public function testGetIndex(): void
    {
        $this->assertSame('admin', AdminArticleReindexProvider::getIndex());
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

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, created = :created WHERE articleUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $article1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $article2->getUuid(),
        ]);

        $config = ReindexConfig::create()->withIndex('admin');
        /** @var array<array{id: string}> $results */
        $results = \iterator_to_array($this->provider->provide($config));

        $this->assertCount(3, $results);

        $expectedResult = [
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article1->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article1->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString1))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Article One EN',
                'locale' => 'en',
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::en',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Article TWO EN',
                'locale' => 'en',
            ],
            [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::de',
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => $article2->getUuid(),
                'changedAt' => (new \DateTimeImmutable($changedDateString2))->format('c'),
                'createdAt' => (new \DateTimeImmutable($createdAt))->format('c'),
                'title' => 'Article TWO DE',
                'locale' => 'de',
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

        $createdAt = '2020-06-01 15:30:00';
        $changedDateString1 = '2023-06-01 15:30:00';
        $changedDateString2 = '2024-11-29 15:30:00';

        $connection = self::getEntityManager()->getConnection();
        $sql = 'UPDATE ar_article_dimension_contents SET changed = :changed, created = :created WHERE articleUuid = :uuid';

        $connection->executeStatement($sql, [
            'changed' => $changedDateString1,
            'created' => $createdAt,
            'uuid' => $article1->getUuid(),
        ]);

        $connection->executeStatement($sql, [
            'changed' => $changedDateString2,
            'created' => $createdAt,
            'uuid' => $article2->getUuid(),
        ]);

        $identifiers = [
            ArticleInterface::RESOURCE_KEY . '::' . $article1->getUuid() . '::en',
            ArticleInterface::RESOURCE_KEY . '::' . $article2->getUuid() . '::de',
        ];

        $config = ReindexConfig::create()
            ->withIndex('admin')
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
