<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

/**
 * @phpstan-type ArticleSmartContentFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: string[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       page: int,
 *       maxPerPage: int|null,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *       audienceTargeting?: bool,
 *       audienceTargeting?: bool,
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *   }
 */
readonly class ArticleSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @var EntityRepository<ArticleInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var EntityRepository<ArticleDimensionContentInterface>
     */
    private EntityRepository $entityDimensionContentRepository;

    /**
     * @var class-string<ArticleDimensionContentInterface>
     */
    private string $articleDimensionContentClassName;

    public function __construct(
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
        EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(ArticleInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(ArticleDimensionContentInterface::class);
        $this->articleDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
    }

    public function getConfiguration(): ProviderConfigurationInterface
    {
        return $this->getConfigurationBuilder()->getConfiguration();
    }

    protected function getConfigurationBuilder(): BuilderInterface
    {
        $builder = Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableSorting(
                [
                    ['column' => 'workflowPublished', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ],
            );

        // TODO
        //        if ($this->hasAudienceTargeting) {
        //            $builder->enableAudienceTargeting();
        //        }

        return $builder;
    }

    /**
     * @param ArticleSmartContentFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var ArticleSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $alias = 'article';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->articleDimensionContentClassName,
            $filters,
            [],
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);

        $queryBuilder->select('COUNT(DISTINCT article.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ArticleSmartContentFilters $filters
     * @param array{
     *     title?: 'asc'|'desc',
     *     authored?: 'asc'|'desc',
     *     workflowPublished?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     *
     * @return array<array{id: string, title: string}>
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        /** @var ArticleSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $alias = 'article';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->articleDimensionContentClassName,
            $filters,
            $sortBys,
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);

        // TODO refactor this part to not use distinct
        // we need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT ' . $alias . '.uuid as id');
        $queryBuilder->addSelect('filterDimensionContent.title');
        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['page'], $filters['limit'], $filters['maxPerPage']);

        /** @var array{id: string, title: string}[] $result */
        $result = $queryBuilder->getQuery()->getArrayResult();

        return $result;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    protected function enhanceWithDimensionAttributes(array $filters): array
    {
        $dimensionAttributes = [
            // we always use the live stage
            'stage' => $filters['stage'] ?? DimensionContentInterface::STAGE_LIVE,
        ];

        return \array_merge($dimensionAttributes, $filters);
    }

    /**
     * @param ArticleSmartContentFilters $filters
     *
     * @return array{
     *         categoryIds?: int[],
     *         categoryOperator: 'AND'|'OR',
     *         websiteCategories: string[],
     *         websiteCategoryOperator: 'AND'|'OR',
     *         tagNames?: string[],
     *         tagOperator: 'AND'|'OR',
     *         websiteTags: string[],
     *         websiteTagOperator: 'AND'|'OR',
     *         templateKeys?: string[],
     *         typesOperator: 'OR',
     *         locale: string,
     *         dataSource: string|null,
     *         limit: int|null,
     *         page: int,
     *         maxPerPage: int|null,
     *         includeSubFolders: bool,
     *         excludeDuplicates: bool,
     *         audienceTargeting?: bool
     *     }
     */
    protected function mapFilters(array $filters): array
    {
        if ($filters['types']) {
            $filters['templateKeys'] = $filters['types'];
            unset($filters['types']);
        }

        if ($filters['categories']) {
            $filters['categoryIds'] = $filters['categories'];
            unset($filters['categories']);
        }

        if ($filters['tags']) {
            $filters['tagNames'] = $filters['tags'];
            unset($filters['tags']);
        }

        return $filters;
    }

    /**
     * @param array{
     *     websiteCategories: string[],
     *     websiteCategoryOperator: 'AND'|'OR',
     *     websiteTags: string[],
     *     websiteTagOperator: 'AND'|'OR',
     *  } $filters
     */
    protected function addInternalFilters(QueryBuilder $queryBuilder, array $filters, string $alias): void
    {
        $websiteCategoryIds = $filters['websiteCategories'];
        if ([] !== $websiteCategoryIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'filterDimensionContent.excerptCategories',
                'websiteFilterCategoryId',
                'id',
                'websiteCategoryIds',
                $websiteCategoryIds,
                $filters['websiteCategoryOperator'],
            );
        }

        $websiteTagNames = $filters['websiteTags'];
        if ([] !== $websiteTagNames) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                'filterDimensionContent.excerptTags',
                'websiteFilterTagName',
                'name',
                'websiteTagNames',
                $websiteTagNames,
                $filters['websiteTagOperator'],
            );
        }
    }

    public function getType(): string
    {
        return ArticleInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return ArticleResourceLoader::RESOURCE_LOADER_KEY;
    }
}
