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
 *       tags: int[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       templateKeys?: string[],
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
 *       offset: int,
 *       includeSubFolders: bool,
 *       excludeDuplicates: bool,
 *       audienceTargeting?: bool,
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *   }
 * @phpstan-type SnippetSmartContentCountFilters array{
 *       categories: int[],
 *       categoryOperator: 'AND'|'OR',
 *       websiteCategories: string[],
 *       websiteCategoryOperator: 'AND'|'OR',
 *       tags: int[],
 *       tagOperator: 'AND'|'OR',
 *       websiteTags: string[],
 *       websiteTagOperator: 'AND'|'OR',
 *       types: string[],
 *       typesOperator: 'OR',
 *       templateKeys?: string[],
 *       locale: string,
 *       dataSource: string|null,
 *       limit: int|null,
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
                    ['column' => 'published', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ]
            );
    }

    /**
     * @param SnippetSmartContentCountFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var SnippetSmartContentCountFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) {
            return 0;
        }

        $alias = 'snippet';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

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
     *     published?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     *
     * @return array<array{id: string, title: string}>
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        /** @var SnippetSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) {
            return [];
        }

        $sortBys = $this->mapSortBys($sortBys);

        $alias = 'snippet';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

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

        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'] ?? 0, $filters['limit']);

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
     * @param SnippetSmartContentFilters|SnippetSmartContentCountFilters $filters
     * @param array<string, mixed> $params
     *
     * @return array{
     *         categoryIds?: int[],
     *         categoryOperator: 'AND'|'OR',
     *         websiteCategories: string[],
     *         websiteCategoryOperator: 'AND'|'OR',
     *         tagIds?: int[],
     *         tagOperator: 'AND'|'OR',
     *         websiteTags: string[],
     *         websiteTagOperator: 'AND'|'OR',
     *         templateKeys: string[]|null,
     *         typesOperator: 'OR',
     *         locale: string,
     *         dataSource: string|null,
     *         limit: int|null,
     *         offset?: int,
     *         includeSubFolders: bool,
     *         excludeDuplicates: bool,
     *         audienceTargeting?: bool
     *     }
     */
    protected function mapFilters(array $filters, array $params = []): array
    {
        $filters['templateKeys'] = $this->resolveTemplateKeys(
            $filters['templateKeys'] ?? [],
            $filters['types'],
            $params,
        );
        unset($filters['types']);

        if ($filters['categories']) {
            $filters['categoryIds'] = $filters['categories'];
            unset($filters['categories']);
        }

        if ($filters['tags']) {
            $filters['tagIds'] = $filters['tags'];
            unset($filters['tags']);
        }

        return $filters;
    }

    /**
     * @param array<string> $existingTemplateKeys
     * @param array<string> $filterTemplateKeys
     * @param array<string, mixed> $params
     *
     * @return list<string>|null null = no overlap with the requested filters
     */
    private function resolveTemplateKeys(array $existingTemplateKeys, array $filterTemplateKeys, array $params): ?array
    {
        $templateKeys = \array_values(\array_unique(\array_merge($existingTemplateKeys, $filterTemplateKeys)));

        $xmlTemplateKeys = $this->parseListParameter($params['templateKeys'] ?? null);
        if ([] !== $xmlTemplateKeys) {
            $templateKeys = [] !== $templateKeys
                ? \array_values(\array_intersect($templateKeys, $xmlTemplateKeys))
                : $xmlTemplateKeys;
            if ([] === $templateKeys) {
                return null;
            }
        }

        return $templateKeys;
    }

    /**
     * @return list<string>
     */
    private function parseListParameter(mixed $value): array
    {
        if (!\is_string($value)) {
            return [];
        }

        return \array_values(\array_filter(\array_map('trim', \explode(',', $value))));
    }

    /**
     * @param array{
     *     title?: 'asc'|'desc',
     *     published?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     *
     * @return array{
     *     title?: 'asc'|'desc',
     *     workflowPublished?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * }
     */
    protected function mapSortBys(array $sortBys): array
    {
        if (\array_key_exists('published', $sortBys)) {
            $sortBys['workflowPublished'] = $sortBys['published'];
            unset($sortBys['published']);
        }

        return $sortBys;
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
