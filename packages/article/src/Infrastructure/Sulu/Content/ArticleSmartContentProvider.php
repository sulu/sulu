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
use Sulu\Article\Infrastructure\Sulu\Admin\ArticleAdmin;
use Sulu\Article\Infrastructure\Sulu\Content\ResourceLoader\ArticleResourceLoader;
use Sulu\Bundle\AdminBundle\Metadata\GroupProviderInterface;
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
 *       audienceTargeting?: bool,
 *       targetGroupId?: int,
 *       segmentKey?: string,
 *       webspaceKey?: string,
 *   }
 * @phpstan-type ArticleSmartContentCountFilters array{
 *        categories: int[],
 *        categoryOperator: 'AND'|'OR',
 *        websiteCategories: string[],
 *        websiteCategoryOperator: 'AND'|'OR',
 *        tags: int[],
 *        tagOperator: 'AND'|'OR',
 *        websiteTags: string[],
 *        websiteTagOperator: 'AND'|'OR',
 *        types: string[],
 *        typesOperator: 'OR',
 *        templateKeys?: string[],
 *        locale: string,
 *        dataSource: string|null,
 *        limit: int|null,
 *        includeSubFolders: bool,
 *        excludeDuplicates: bool,
 *        audienceTargeting?: bool,
 *        audienceTargeting?: bool,
 *        targetGroupId?: int,
 *        segmentKey?: string,
 *        webspaceKey?: string,
 *    }
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
        private GroupProviderInterface $groupProvider,
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
                    ['column' => 'published', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ],
            )
            ->enableTypes(\array_values(\array_map(
                function($group) {
                    return [
                        'title' => $group->title,
                        'type' => $group->identifier,
                    ];
                },
                $this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE),
            )))
            ->enableProperties([
                'title' => 'title',
                'url' => 'url',
            ])
            ->enableView(
                ArticleAdmin::EDIT_TABS_VIEW . '_{group}',
                ['id' => 'id', 'locale' => 'locale'],
                ['group' => 'group'],
            );

        // TODO
        //        if ($this->hasAudienceTargeting) {
        //            $builder->enableAudienceTargeting();
        //        }

        return $builder;
    }

    /**
     * @param ArticleSmartContentCountFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var ArticleSmartContentCountFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) { // means admin or website requested templates and defined groups or templates do not match together so we can early return with zero results
            return 0;
        }

        $alias = 'article';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

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
     *     published?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     *
     * @return array<array{id: string, title: string, group: string, locale: string}>
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        /** @var ArticleSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) { // means admin or website requested templates and defined groups or templates do not match together so we can early return with no results
            return [];
        }

        $sortBys = $this->mapSortBys($sortBys);

        $alias = 'article';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

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
        $queryBuilder->addSelect('filterDimensionContent.templateKey');
        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'] ?? 0, $filters['limit']);

        /** @var array{id: string, title: string, templateKey: string|null}[] $result */
        $result = $queryBuilder->getQuery()->getArrayResult();

        foreach ($result as &$item) {
            $templateKey = $item['templateKey'];
            $item['group'] = $this->groupProvider->resolveGroup(
                ArticleInterface::TEMPLATE_TYPE,
                \is_string($templateKey) ? $templateKey : null,
            );
            $item['locale'] = $filters['locale'];
            unset($item['templateKey']);
        }
        unset($item);

        /** @var array{id: string, title: string, group: string, locale: string}[] $result */
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
     * @param ArticleSmartContentFilters|ArticleSmartContentCountFilters $filters
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
     *         audienceTargeting?: bool,
     *         webspaceKey?: string
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
     * @param array<string> $filterGroupIdentifiers
     * @param array<string, mixed> $params
     *
     * @return list<string>|null null = no overlap with the requested filters
     */
    private function resolveTemplateKeys(array $existingTemplateKeys, array $filterGroupIdentifiers, array $params): ?array
    {
        $xmlGroupIdentifiers = $this->parseListParameter($params['groups'] ?? null);

        if ([] !== $xmlGroupIdentifiers && [] !== $filterGroupIdentifiers) {
            $groupIdentifiers = \array_values(\array_intersect($filterGroupIdentifiers, $xmlGroupIdentifiers));
            if ([] === $groupIdentifiers) {
                return null;
            }
        } else {
            $groupIdentifiers = $filterGroupIdentifiers ?: $xmlGroupIdentifiers;
        }

        $templateKeys = \array_values($existingTemplateKeys);
        if ([] !== $groupIdentifiers) {
            $templatesFromGroups = $this->expandGroupsToTemplates($groupIdentifiers);
            $templateKeys = [] !== $templateKeys
                ? \array_values(\array_intersect($templateKeys, $templatesFromGroups))
                : $templatesFromGroups;
            if ([] === $templateKeys) {
                return null;
            }
        }

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
     * @param array<string> $identifiers
     *
     * @return list<string>
     */
    private function expandGroupsToTemplates(array $identifiers): array
    {
        $templates = [];
        foreach ($this->groupProvider->getGroups(ArticleInterface::TEMPLATE_TYPE) as $group) {
            if (\in_array($group->identifier, $identifiers, true)) {
                $templates = \array_merge($templates, \array_filter($group->templates, 'is_string'));
            }
        }

        return \array_values(\array_unique($templates));
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
     *     webspaceKey?: string,
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

        $webspaceKey = $filters['webspaceKey'] ?? null;
        if (null !== $webspaceKey) {
            $queryBuilder->leftJoin('filterDimensionContent.additionalWebspaces', 'additionalWebspace');
            $queryBuilder->andWhere('filterDimensionContent.mainWebspace = :webspaceKey OR additionalWebspace.additionalWebspace = :webspaceKey');
            $queryBuilder->setParameter('webspaceKey', $webspaceKey);
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
