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
use Sulu\Article\Domain\Model\ArticleDimensionContentAdditionalWebspace;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Search\Visitor\WebsiteArticleReindexProviderEnhancerInterface;
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
 *     dimensionContentId: int,
 *     authored: \DateTimeImmutable|null,
 * }
 *
 * @internal this class is internal no backwards compatibility promise is given for this class
 *            use Symfony Dependency Injection to override or create your own ReindexProvider instead
 */
final class WebsiteArticleReindexProvider implements ReindexProviderInterface
{
    private const BATCH_SIZE = 100;

    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    private EntityRepository $dimensionContentRepository;

    /**
     * @var EntityRepository<ArticleDimensionContentAdditionalWebspace>
     */
    private EntityRepository $additionalWebspacesRepository;

    /**
     * @param iterable<WebsiteArticleReindexProviderEnhancerInterface> $enhancers
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        private iterable $enhancers = [],
    ) {
        $dimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);
        $additionalWebspacesRepository = $entityManager->getRepository(ArticleDimensionContentAdditionalWebspace::class);

        $this->dimensionContentRepository = $dimensionContentRepository;
        $this->additionalWebspacesRepository = $additionalWebspacesRepository;
    }

    public function total(): ?int
    {
        return null;
    }

    public function provide(ReindexConfig $reindexConfig): \Generator
    {
        $identifiers = $reindexConfig->getIdentifiers();
        $offset = 0;
        $batch = $this->loadBatch($identifiers, $offset);

        while ([] !== $batch) {
            $dimensionContentIds = \array_column($batch, 'dimensionContentId');
            $additionalWebspacesResult = $this->loadAdditionalWebspaces($dimensionContentIds);

            /** @var Article $article */
            foreach ($batch as $article) {
                $authoredAt = $article['authored'] ?? $article['changed'];
                $webspaces = $article['mainWebspace'] ? [$article['mainWebspace']] : [];

                foreach ($additionalWebspacesResult as $additionalWebspaceRow) {
                    if ($additionalWebspaceRow['articleDimensionContentId'] === $article['dimensionContentId'] && !\in_array($additionalWebspaceRow['webspace'], $webspaces, true)) {
                        $webspaces[] = $additionalWebspaceRow['webspace'];
                    }
                }

                $data = [
                    'id' => ArticleInterface::RESOURCE_KEY . '__' . ((string) $article['articleId']) . '__' . $article['locale'],
                    'resourceKey' => ArticleInterface::RESOURCE_KEY,
                    'resourceId' => (string) $article['articleId'],
                    'locale' => $article['locale'],
                    'webspaces' => $webspaces,
                    'title' => '',
                    'url' => $article['slug'],
                    'content' => [],
                    'mediaId' => '',
                    'authoredAt' => $authoredAt->format('c'),
                    'metadata' => [],
                ];

                foreach ($this->enhancers as $enhancer) {
                    $data = $enhancer->enhanceDocument($article, $data);
                }

                if ('' === $data['title']) {
                    $data['title'] = $article['title'];
                }

                yield $data;
            }

            $offset += self::BATCH_SIZE;
            $batch = $this->loadBatch($identifiers, $offset);
        }
    }

    /**
     * @param string[] $identifiers
     *
     * @return array<int, Article>
     */
    private function loadBatch(array $identifiers, int $offset): array
    {
        $queryBuilder = $this->dimensionContentRepository->createQueryBuilder('dimensionContent')
            ->leftJoin('dimensionContent.route', 'route')
            ->select('IDENTITY(dimensionContent.article) AS articleId')
            ->addSelect('dimensionContent.authored')
            ->addSelect('dimensionContent.changed')
            ->addSelect('dimensionContent.title')
            ->addSelect('dimensionContent.locale')
            ->addSelect('dimensionContent.mainWebspace')
            ->addSelect('dimensionContent.id AS dimensionContentId')
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
                $resourceKey = \explode('__', $identifier)[0];

                if (ArticleInterface::RESOURCE_KEY !== $resourceKey) {
                    continue;
                }

                $id = \explode('__', $identifier)[1] ?? '';
                $locale = \explode('__', $identifier)[2] ?? '';

                $conditions[] = "(dimensionContent.article = :id{$index} AND dimensionContent.locale = :locale{$index})";
                $parameters["id{$index}"] = $id;
                $parameters["locale{$index}"] = $locale;
            }

            if (!$conditions) {
                return [];
            }

            $queryBuilder->andWhere(\implode(' OR ', $conditions));
        }

        foreach ($parameters as $parameterKey => $parameterValue) {
            $queryBuilder->setParameter($parameterKey, $parameterValue);
        }

        foreach ($this->enhancers as $enhancer) {
            $enhancer->enhanceQuery($queryBuilder);
        }

        $queryBuilder->orderBy('dimensionContent.id', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults(self::BATCH_SIZE);

        /** @var array<int, Article> */
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int[] $dimensionContentIds
     *
     * @return array<int, array{articleDimensionContentId: int, webspace: string}>
     */
    private function loadAdditionalWebspaces(array $dimensionContentIds = []): array
    {
        if (0 === \count($dimensionContentIds)) {
            return [];
        }

        $queryBuilder = $this->additionalWebspacesRepository->createQueryBuilder('additionalWebspace')
            ->select('IDENTITY(additionalWebspace.articleDimensionContent) AS articleDimensionContentId')
            ->addSelect('additionalWebspace.additionalWebspace AS webspace')
            ->where('additionalWebspace.articleDimensionContent IN (:dimensionContentIds)')
            ->setParameter('dimensionContentIds', $dimensionContentIds);

        return $queryBuilder->getQuery()->getResult();
    }

    public static function getIndex(): string
    {
        return 'website';
    }
}
