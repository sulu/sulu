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

use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Domain\Repository\ArticleRepositoryInterface;
use Sulu\Article\Tests\Traits\CreateArticleTrait;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Content\Domain\Model\DimensionContentInterface;

class ArticleRepositoryTest extends SuluTestCase
{
    use CreateArticleTrait;

    private ArticleRepositoryInterface $articleRepository;

    protected function setUp(): void
    {
        self::purgeDatabase();

        $this->articleRepository = self::getContainer()->get('sulu_article.article_repository');
    }

    public function testFindIdentifiersBySortedByAuthored(): void
    {
        $old = $this->createArticleIn('Old', '2024-01-01T10:00:00', 'sulu-io');
        $newest = $this->createArticleIn('Newest', '2026-01-01T10:00:00', 'sulu-io');
        $middle = $this->createArticleIn('Middle', '2025-01-01T10:00:00', 'sulu-io');

        $uuids = $this->articleRepository->findIdentifiersBy($this->liveFilters(), ['authored' => 'desc']);

        $this->assertSame([$newest->getUuid(), $middle->getUuid(), $old->getUuid()], [...$uuids]);
    }

    public function testFindIdentifiersByWebspaceKey(): void
    {
        $sulu = $this->createArticleIn('Sulu', '2026-01-01T10:00:00', 'sulu-io');
        $blog = $this->createArticleIn('Blog', '2025-01-01T10:00:00', 'blog');
        $both = $this->createArticleIn('Both', '2024-01-01T10:00:00', 'sulu-io', ['blog']);

        $uuids = $this->articleRepository->findIdentifiersBy(
            $this->liveFilters() + ['webspaceKey' => 'blog'],
            ['authored' => 'desc'],
        );

        $this->assertSame([$blog->getUuid(), $both->getUuid()], [...$uuids]);
        $this->assertNotContains($sulu->getUuid(), [...$uuids]);
    }

    /**
     * @return array{locale: string, stage: string, version: int}
     */
    private function liveFilters(): array
    {
        return [
            'locale' => 'en',
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];
    }

    /**
     * @param string[] $additionalWebspaces
     */
    private function createArticleIn(string $title, string $authored, string $mainWebspace, array $additionalWebspaces = []): ArticleInterface
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
                    'additionalWebspaces' => $additionalWebspaces,
                ],
            ],
        ]);
    }
}
