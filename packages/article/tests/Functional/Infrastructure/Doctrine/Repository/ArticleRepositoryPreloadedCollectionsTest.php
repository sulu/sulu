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

namespace Sulu\Article\Tests\Functional\Infrastructure\Doctrine\Repository;

use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Tests\Functional\Traits\QueryCountTrait;

class ArticleRepositoryPreloadedCollectionsTest extends SuluTestCase
{
    use CreateArticleTrait;
    use QueryCountTrait;

    private ArticleRepositoryInterface $articleRepository;
    private ContentAggregatorInterface $contentAggregator;

    /**
     * @var string[]
     */
    private array $uuids = [];

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->articleRepository = self::getContainer()->get('sulu_article.article_repository');
        $this->contentAggregator = self::getContainer()->get('sulu_content.content_aggregator');

        foreach (['a', 'b', 'c'] as $key) {
            $this->uuids[] = self::createArticle([
                'en' => ['live' => [
                    'template' => 'article',
                    'title' => 'Article ' . $key,
                    'url' => '/article-' . $key,
                    'mainWebspace' => 'sulu-io',
                    'additionalWebspaces' => ['blog'],
                    'customizeWebspaceSettings' => true,
                ]],
            ])->getUuid();
        }

        self::getEntityManager()->clear();
    }

    public function testFindByMergesTheCompleteCollections(): void
    {
        $articles = \iterator_to_array($this->articleRepository->findBy(
            ['uuids' => $this->uuids, 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true],
        ), false);

        $this->assertCount(3, $articles);
        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections($articles)));
    }

    public function testGetOneByMergesTheCompleteCollections(): void
    {
        $article = $this->articleRepository->getOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true],
        );

        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections([$article])));
    }

    public function testFindOneByMergesTheCompleteCollections(): void
    {
        $article = $this->articleRepository->findOneBy(
            ['uuid' => $this->uuids[0], 'locale' => 'en', 'stage' => DimensionContentInterface::STAGE_LIVE],
            [ArticleRepositoryInterface::GROUP_SELECT_ARTICLE_WEBSITE => true],
        );
        $this->assertNotNull($article);

        $this->assertSame(0, self::countQueries(fn () => $this->assertMergedCollections([$article])));
    }

    /**
     * @param iterable<ArticleInterface> $articles
     */
    private function assertMergedCollections(iterable $articles): void
    {
        foreach ($articles as $article) {
            /** @var ArticleDimensionContentInterface $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($article, [
                'locale' => 'en',
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);

            $this->assertSame(['blog'], $dimensionContent->getAdditionalWebspaces());
        }
    }
}
