<?php

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
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @phpstan-type PageSmartContentFilters array{
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

    public function __construct(
        private DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private MetadataProviderInterface $formMetadataProvider,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
        private ?TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
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
                    ['column' => 'workflowPublished', 'title' => 'sulu_admin.published'],
                    ['column' => 'authored', 'title' => 'sulu_admin.authored'],
                    ['column' => 'created', 'title' => 'sulu_admin.created'],
                    ['column' => 'changed', 'title' => 'sulu_admin.changed'],
                    ['column' => 'title', 'title' => 'sulu_admin.title'],
                ],
            )
            ->enableTypes($this->getTypes())
            ->enableView(PageAdmin::EDIT_FORM_VIEW, ['id' => 'id', 'webspace' => 'webspace']);

        // TODO
        //        if ($this->hasAudienceTargeting) {
        //            $builder->enableAudienceTargeting();
        //        }

        return $builder;
    }

    /**
     * @param PageSmartContentFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /** @var PageSmartContentFilters $filters */
        $filters = $this->enhanceWithDimensionAttributes($filters);

        $alias = 'page';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->pageDimensionContentClassName,
            $filters,
            [],
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);

        $queryBuilder->select('COUNT(DISTINCT page.uuid)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param PageSmartContentFilters $filters
     * @param array{
     *     title?: 'asc'|'desc',
     *     authored?: 'asc'|'desc',
     *     workflowPublished?: 'asc'|'desc',
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

        $alias = 'page';
        $queryBuilder = $this->entityRepository->createQueryBuilder($alias);

        $filters = $this->mapFilters($filters);
        $this->dimensionContentQueryEnhancer->addFilters(
            $queryBuilder,
            $alias,
            $this->pageDimensionContentClassName,
            $filters,
            $sortBys,
        );
        $this->addInternalFilters($queryBuilder, $filters, $alias);

        // TODO refactor this part to not use distinct
        // we need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT page.uuid as id');
        $queryBuilder->addSelect('page.webspaceKey as webspace');
        $queryBuilder->addSelect('filterDimensionContent.title');
        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);

        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['page'], $filters['limit'], $filters['maxPerPage']);

        /** @var array{id: string, title: string, webspace: string}[] $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

        return $queryResult;
    }

    /**
     * @param PageSmartContentFilters $filters
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
     *        templateKeys?: string[],
     *        typesOperator: 'OR',
     *        locale: string,
     *        dataSource: string|null,
     *        limit: int|null,
     *        page: int,
     *        maxPerPage: int|null,
     *        includeSubFolders: bool,
     *        excludeDuplicates: bool,
     *        audienceTargeting?: bool,
     *        targetGroupId?: int,
     *        segmentKey?: string,
     *    }
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
                'datasourcePage.uuid = :datasource'
            )
                ->andWhere($alias . '.lft >= datasourcePage.lft')
                ->andWhere($alias . '.rgt <= datasourcePage.rgt')
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
