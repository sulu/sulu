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

namespace Sulu\Article\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\OrderBy;
use Sulu\Article\Domain\Model\ArticleDimensionContentInterface;
use Sulu\Article\Domain\Model\ArticleInterface;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;

class ArticleSmartContentProvider implements SmartContentProviderInterface
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
        private readonly DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
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
        return Builder::create()
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
    }

    /**
     * @param array{
     *     locale?: string|null,
     *     categories?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagIds?: int[],
     *     tagOperator?: 'AND'|'OR',
     *     types?: string[],
     *     loadGhost?: bool,
     * } $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /**
         * @var array{
         *     locale?: string|null,
         *     categories?: int[],
         *     categoryOperator?: 'AND'|'OR',
         *     tagIds?: int[],
         *     tagOperator?: 'AND'|'OR',
         *     types?: string[],
         *     loadGhost?: bool,
         *     stage: string,
         * } $filters
         */
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
        $queryBuilder->select('COUNT(DISTINCT article.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array{
     *     locale?: string|null,
     *     categories?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tags?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     types?: string[],
     *     loadGhost?: bool,
     *     limit?: int,
     *     page?: int,
     * } $filters
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
        /**
         * @var array{
         *      locale?: string|null,
         *      categories?: int[],
         *      categoryOperator?: 'AND'|'OR',
         *      tags?: string[],
         *      tagOperator?: 'AND'|'OR',
         *      types?: string[],
         *      loadGhost?: bool,
         *      limit?: int,
         *      page?: int,
         *      stage: string,
         *  } $filters
         */
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

        if (($page = ($filters['page'] ?? null))
            && ($limit = ($filters['limit'] ?? null))) {
            $this->dimensionContentQueryEnhancer->addPagination($queryBuilder, $page, $limit);
        }

        // TODO refactor this part to not use distinct
        // we need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT article.uuid as id');
        $queryBuilder->addSelect('filterDimensionContent.title');

        /** @var OrderBy[]|null $queryParts */
        $queryParts = $queryBuilder->getDQLPart('orderBy');
        foreach ($queryParts ?? [] as $orderBy) {
            foreach ($orderBy->getParts() as $order) {
                [$column] = \explode(' ', $order);
                $queryBuilder->addSelect($column);
            }
        }

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
     * @param array{
     *     locale?: string|null,
     *     categories?: array<int>,
     *     categoryOperator?: 'AND'|'OR',
     *     tags?: array<string>,
     *     tagOperator?: 'AND'|'OR',
     *     types?: array<string>,
     *     loadGhost?: bool,
     *     types?: array<string>,
     *     webspaceKey?: string,
     *     dataSource?: string|null,
     *     page?: int,
     *     limit?: int,
     *     stage?: string,
     * } $filters
     *
     * @return array{
     *     locale?: string|null,
     *     stage?: string|null,
     *     categoryIds?: array<int>,
     *     categoryOperator?: 'AND'|'OR',
     *     tagNames?: array<string>,
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: array<string>,
     *     loadGhost?: bool,
     *     webspaceKey?: string,
     *     dataSource?: string|null,
     *     page?: int,
     *     limit?: int,
     * }
     */
    protected function mapFilters(array $filters): array
    {
        if ($filters['types'] ?? null) {
            $filters['templateKeys'] = $filters['types'];
            unset($filters['types']);
        }

        if ($filters['categories'] ?? null) {
            $filters['categoryIds'] = $filters['categories'];
            unset($filters['categories']);
        }

        if ($filters['tags'] ?? null) {
            $filters['tagNames'] = $filters['tags'];
            unset($filters['tags']);
        }

        return $filters;
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
