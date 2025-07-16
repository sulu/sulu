<?php

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
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Snippet\Domain\Model\SnippetDimensionContentInterface;
use Sulu\Snippet\Domain\Model\SnippetInterface;
use Sulu\Snippet\Infrastructure\Sulu\Content\ResourceLoader\SnippetResourceLoader;

/**
 * @phpstan-type SnippetSmartContentFilters array{
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
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *   }
 */
readonly class SnippetSmartContentProvider implements SmartContentProviderInterface
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
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
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
            ->enableAudienceTargeting()
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
     * @param SnippetSmartContentFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var SnippetSmartContentFilters $filters */
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
        $this->addInternalFilters($queryBuilder, $filters, $alias);
        $queryBuilder->select('COUNT(DISTINCT snippet.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param SnippetSmartContentFilters $filters
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
        $alias = 'snippet';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        /** @var SnippetSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);
        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->snippetDimensionContentClassName,
            $filters,
            $sortBys,
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);

        // TODO refactor this to not use distinct
        // We need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT snippet.uuid as id');
        $queryBuilder->addSelect('filterDimensionContent.title');
        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);

        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['page'], $filters['limit'], $filters['maxPerPage']);

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
     * @param SnippetSmartContentFilters $filters
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
        return SnippetInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return SnippetResourceLoader::RESOURCE_LOADER_KEY;
    }
}
