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

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Bundle\SecurityBundle\AccessControl\AccessControlQueryEnhancer;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @phpstan-type PageSmartContentFilters array{
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
 *       webspaceKey?: string,
 *   }
 * @phpstan-type PageSmartContentCountFilters array{
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
 *       webspaceKey?: string,
 *   }
 */
readonly class PageSmartContentProvider implements SmartContentProviderInterface
{
    /**
     * @var EntityRepository<PageInterface>
     */
    private EntityRepository $entityRepository;

    /**
     * @var EntityRepository<PageDimensionContentInterface>
     */
    private EntityRepository $entityDimensionContentRepository;

    /**
     * @var class-string<PageDimensionContentInterface>
     */
    private string $pageDimensionContentClassName;

    /**
     * @param array<string, mixed> $bundles
     * @param mixed[]|null $permissions
     */
    public function __construct(
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private MetadataProviderInterface $formMetadataProvider,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
        private ?TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        private array $bundles,
        private WebspaceManagerInterface $webspaceManager,
        private AccessControlQueryEnhancer $accessControlQueryEnhancer,
        private ?Security $security,
        private ?array $permissions = null,
    ) {
        $this->entityRepository = $entityManager->getRepository(PageInterface::class);
        $this->entityDimensionContentRepository = $entityManager->getRepository(PageDimensionContentInterface::class);
        $this->pageDimensionContentClassName = $this->entityDimensionContentRepository->getClassName();
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
            ->enableDatasource(PageInterface::RESOURCE_KEY, PageInterface::RESOURCE_KEY, 'column_list')
            ->enableSorting(
                [
                    ['column' => '', 'title' => 'sulu_admin.default'],
                    ['column' => 'published', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ],
            )
            ->enableTypes($this->getTypes())
            ->enableView(PageAdmin::EDIT_FORM_VIEW, ['id' => 'id', 'webspace' => 'webspace'])
            ->enableProperties([
                'title' => 'title',
                'url' => 'url',
            ]);

        if ($this->bundles['SuluAudienceTargetingBundle'] ?? false) {
            $builder->enableAudienceTargeting();
        }

        return $builder;
    }

    /**
     * @param PageSmartContentCountFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var PageSmartContentCountFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) {
            return 0;
        }

        $alias = 'page';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->pageDimensionContentClassName,
            $filters,
            [],
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);
        $this->enhanceQueryBuilderWithAccessControl($queryBuilder, $filters, $alias);

        $queryBuilder->select('COUNT(DISTINCT page.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param PageSmartContentFilters $filters
     * @param array{
     *     title?: 'asc'|'desc',
     *     authored?: 'asc'|'desc',
     *     published?: 'asc'|'desc',
     *     created?: 'asc'|'desc',
     *     changed?: 'asc'|'desc',
     * } $sortBys
     *
     * @return array<array{id: string, title: string, webspace: string}>
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        /** @var PageSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $filters = $this->mapFilters($filters, $params);
        if (null === $filters['templateKeys']) {
            return [];
        }

        $sortBys = $this->mapSortBys($sortBys);

        $alias = 'page';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->pageDimensionContentClassName,
            $filters,
            $sortBys,
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);
        $this->addInternalSortBys($queryBuilder, $sortBys, $alias);
        $this->enhanceQueryBuilderWithAccessControl($queryBuilder, $filters, $alias);

        // TODO refactor this part to not use distinct
        // we need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT page.uuid as id');
        $queryBuilder->addSelect('page.webspaceKey as webspace');
        $queryBuilder->addSelect('filterDimensionContent.title');
        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);
        $limit = \is_numeric($filters['limit']) ? (int) $filters['limit'] : null;
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'] ?? 0, $limit);

        /** @var array{id: string, title: string, webspace: string}[] $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

        return $queryResult;
    }

    /**
     * @param PageSmartContentFilters|PageSmartContentCountFilters $filters
     * @param array<string, mixed> $params
     *
     * @return array{
     *        categoryIds?: int[],
     *        categoryOperator: 'AND'|'OR',
     *        websiteCategories: string[],
     *        websiteCategoryOperator: 'AND'|'OR',
     *        tagNames?: string[],
     *        tagOperator: 'AND'|'OR',
     *        websiteTags: string[],
     *        websiteTagOperator: 'AND'|'OR',
     *        templateKeys: string[]|null,
     *        typesOperator: 'OR',
     *        locale: string,
     *        dataSource: string|null,
     *        limit: int|null,
     *        offset?: int,
     *        includeSubFolders: bool,
     *        excludeDuplicates: bool,
     *        audienceTargeting?: bool,
     *        targetGroupId?: int,
     *        segmentKey?: string,
     *    }
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
     * Enhances the query builder with access control filtering if webspace has security enabled.
     *
     * @param array{webspaceKey?: string} $filters
     */
    private function enhanceQueryBuilderWithAccessControl(
        QueryBuilder $queryBuilder,
        array $filters,
        string $alias
    ): void {
        $webspace = $this->webspaceManager->findWebspaceByKey($filters['webspaceKey'] ?? null);
        /** @var UserInterface|null $user */
        $user = $webspace && $webspace->hasWebsiteSecurity() && $this->security ? $this->security->getUser() : null;
        /** @var int|null $permission */
        $permission = $webspace && $webspace->hasWebsiteSecurity() && $this->permissions
            ? $this->permissions[PermissionTypes::VIEW]
            : null;

        if ($permission) {
            $this->accessControlQueryEnhancer->enhance(
                $queryBuilder,
                $user,
                $permission,
                Page::class,
                $alias,
                'uuid'
            );
        }
    }

    /**
     * @param array{
     *     dataSource: string|null,
     *     includeSubFolders: bool,
     *     websiteCategories: string[],
     *     websiteCategoryOperator: 'AND'|'OR',
     *     websiteTags: string[],
     *     websiteTagOperator: 'AND'|'OR',
     *  } $filters
     */
    protected function addInternalFilters(QueryBuilder $queryBuilder, array $filters, string $alias): void
    {
        $datasource = $filters['dataSource'] ?? null;
        if ($filters['includeSubFolders']) {
            // Join with the dataSource page to get its lft and rgt values
            $queryBuilder->leftJoin(
                PageInterface::class,
                'datasourcePage',
                Join::WITH,
                'datasourcePage.uuid = :datasource',
            )
                ->andWhere($alias . '.lft > datasourcePage.lft')
                ->andWhere($alias . '.rgt < datasourcePage.rgt')
                ->setParameter('datasource', $datasource);
        } else {
            $queryBuilder->andWhere($alias . '.parent = :datasource')
                ->setParameter('datasource', $datasource);
        }

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

    /**
     * @param array<string, string> $sortBys
     */
    protected function addInternalSortBys(QueryBuilder $queryBuilder, array $sortBys, string $alias): void
    {
        if ([] === $sortBys) {
            $queryBuilder->addOrderBy($alias . '.lft', 'ASC');

            return;
        }

        foreach ($sortBys as $sortBy => $sortMethod) {
            if ('' !== $sortBy) {
                continue;
            }

            $queryBuilder->addOrderBy($alias . '.lft', $sortMethod);
        }
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
     * @return array{type: string, title: string}[]
     */
    private function getTypes(): array
    {
        $types = [];
        if ($this->tokenStorage && null !== $this->tokenStorage->getToken()) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (!$user instanceof UserInterface) {
                return $types;
            }

            $locale = $user->getLocale();
            /** @var TypedFormMetadata $metadata */
            $metadata = $this->formMetadataProvider->getMetadata('page', $locale, []);

            foreach ($metadata->getForms() as $form) {
                $types[] = ['type' => $form->getName(), 'title' => $form->getTitle($locale)];
            }
        }

        return $types;
    }

    public function getType(): string
    {
        return PageInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return PageResourceLoader::RESOURCE_LOADER_KEY;
    }
}
