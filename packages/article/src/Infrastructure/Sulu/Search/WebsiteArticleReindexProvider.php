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

namespace Sulu\Article\Infrastructure\Sulu\Search;

use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * @phpstan-type Article array{
 *     articleId: int,
 *     changed: \DateTimeImmutable,
 *     title: string,
 *     locale: string,
 *     mainWebspace: string|null,
 *     additionalWebspaces: string[]|null,
 *     slug: string,
 *     authored: \DateTimeImmutable|null,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class WebsiteArticleReindexProvider implements ReindexProviderInterface
{
    /**
     * @var EntityRepository<ArticleInterface>
     */
    protected EntityRepository $articleRepository;

    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    protected EntityRepository $dimensionContentRepository;

    /**
     * @var array<string, string>|null
     */
    private ?array $defaultMainWebspace;

    /**
     * @var array<string, string>|null
     */
    private ?array $defaultAdditionalWebspaces;

    /**
     * @param array<string, string>|null $defaultMainWebspace
     * @param array<string, string>|null $defaultAdditionalWebspaces
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ?array $defaultMainWebspace,
        ?array $defaultAdditionalWebspaces,
    ) {
        $repository = $entityManager->getRepository(ArticleInterface::class);
        $dimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);

        $this->articleRepository = $repository;
        $this->dimensionContentRepository = $dimensionContentRepository;
        $this->defaultMainWebspace = $defaultMainWebspace;
        $this->defaultAdditionalWebspaces = $defaultAdditionalWebspaces;
    }

    public function total(): ?int
    {
        // Todo: Add correct count for multiple locales.
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $articles = $this->loadArticles($reindexConfig->getIdentifiers());

        /** @var Article $article */
        foreach ($articles as $article) {
            $authoredAt = $article['authored'] ?? $article['changed'];
            $webspaces = \array_merge(
                $article['mainWebspace'] ? [$article['mainWebspace']] : [],
                $article['additionalWebspaces'] ?? [],
            );
            $webspaces = \array_values(\array_unique($webspaces));

            if (0 === \count($webspaces)) {
                $defaultMainWebspace = $this->defaultMainWebspace ? ($this->defaultMainWebspace['default'] ? [$this->defaultMainWebspace['default']] : []) : [];
                /** @var string[] $defaultAdditionalWebspaces */
                $defaultAdditionalWebspaces = $this->defaultAdditionalWebspaces ? ($this->defaultAdditionalWebspaces['default'] ?? []) : [];

                $webspaces = \array_merge(
                    $defaultMainWebspace,
                    $defaultAdditionalWebspaces,
                );
            }

            yield [
                'id' => ArticleInterface::RESOURCE_KEY . '::' . ((string) $article['articleId']) . '::' . $article['locale'],
                'resourceKey' => ArticleInterface::RESOURCE_KEY,
                'resourceId' => (string) $article['articleId'],
                'locale' => $article['locale'],
                'webspaces' => $webspaces,
                'title' => $article['title'],
                'url' => $article['slug'],
                'content' => [], // Todo: Add content.
                'mediaId' => '',
                'authoredAt' => $authoredAt->format('c'),
            ];
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return iterable<Article>
     */
    private function loadArticles(array $identifiers = []): iterable
    {
        $qb = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->leftJoin('dimensionContent.route', 'route')
            ->select('IDENTITY(dimensionContent.article) AS articleId')
            ->addSelect('dimensionContent.authored')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('dimensionContent.mainWebspace')
            ->addSelect('dimensionContent.additionalWebspaces')
            ->addSelect('route.slug')

            ->where('dimensionContent.stage = :stage')
            ->andWhere('dimensionContent.locale IS NOT NULL')
            ->andWhere('dimensionContent.version = :version');

        $parameters = [
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        if (0 < \count($identifiers)) {
            $conditions = [];

            foreach ($identifiers as $index => $identifier) {
                $resourceKey = \explode('::', $identifier)[0];

                if (ArticleInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('::', $identifier)[1] ?? '';
                $locale = \explode('::', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.article = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $qb->andWhere(\implode(' OR ', $conditions));
        }

        $qb->setParameters($parameters);

        /** @var iterable<Article> */
        return $qb->getQuery()->toIterable();
    }

    public static function getIndex(): string
    {
        return 'website';
    }
}
