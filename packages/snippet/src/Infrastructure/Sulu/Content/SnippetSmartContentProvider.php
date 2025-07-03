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

namespace Sulu\Snippet\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\OrderBy;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

class SnippetSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @var EntityRepository<SnippetInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var EntityRepository<SnippetDimensionContentInterface>
     */
    private EntityRepository $entityDimensionContentRepository;

    /**
     * @var class-string<SnippetDimensionContentInterface>
     */
    private string $snippetDimensionContentClassName;

    public function __construct(
        private readonly DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        EntityManagerInterface $entityManager,
    ) {
        $this->entityRepository = $entityManager->getRepository(SnippetInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(SnippetDimensionContentInterface::class);
        $this->snippetDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
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
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ]
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
         *     stage: string,
         * } $filters
         */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $alias = 'snippet';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->snippetDimensionContentClassName,
            $filters,
            [],
        );
        $queryBuilder->select('COUNT(DISTINCT snippet.uuid)');

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
     *     limit?: int,
     *     page?: int,
     * } $filters
     * @param array{
     *     title?: 'asc'|'desc',
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
         *      limit?: int,
         *      page?: int,
         *      stage: string,
         *  } $filters
         */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $alias = 'snippet';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->snippetDimensionContentClassName,
            $filters,
            $sortBys,
        );

        if (($page = ($filters['page'] ?? null))
            && ($limit = ($filters['limit'] ?? null))) {
            $this->dimensionContentQueryEnhancer->addPagination($queryBuilder, $page, $limit);
        }

        // TODO refactor this to not use distinct
        // We need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT snippet.uuid as id');
        $queryBuilder->addSelect('filterDimensionContent.title');

        /** @var OrderBy[]|null $queryParts */
        $queryParts = $queryBuilder->getDQLPart('orderBy');
        foreach ($queryParts ?? [] as $orderBy) {
            foreach ($orderBy->getParts() as $order) {
                [$column] = \explode(' ', $order);
                $queryBuilder->addSelect($column);
            }
        }

        /** @var array{id: string, title: string, changed?: string, authored?: string}[] $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

        /** @var array{id: string, title: string}[] $result */
        $result = \array_map(
            static fn (array $item) => [
                'id' => $item['id'],
                'title' => $item['title'],
            ],
            $queryResult
        );

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
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return SnippetResourceLoader::RESOURCE_LOADER_KEY;
    }
}
