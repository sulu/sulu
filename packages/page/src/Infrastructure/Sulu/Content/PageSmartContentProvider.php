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
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\BuilderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Sulu\Page\Infrastructure\Sulu\Admin\PageAdmin;
use Sulu\Page\Infrastructure\Sulu\Content\ResourceLoader\PageResourceLoader;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class PageSmartContentProvider implements SmartContentProviderInterface
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
        private readonly DimensionContentQueryEnhancer $dimensionContentQueryEnhancer,
        private MetadataProviderInterface $formMetadataProvider,
        private TokenStorageInterface $tokenStorage,
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

    /**
     * @param array{
     *     locale?: string|null,
     *     categoryIds?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagNames?: int[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     * } $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        /**
         * @var array{
         *     locale?: string|null,
         *     categoryIds?: int[],
         *     categoryOperator?: 'AND'|'OR',
         *     tagNames?: int[],
         *     tagOperator?: 'AND'|'OR',
         *     templateKeys?: string[],
         *     loadGhost?: bool,
         *     stage: string,
         * } $filters
         */
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
     * @param array{
     *     locale?: string|null,
     *     categoryIds?: int[],
     *     categoryOperator?: 'AND'|'OR',
     *     tagNames?: string[],
     *     tagOperator?: 'AND'|'OR',
     *     templateKeys?: string[],
     *     loadGhost?: bool,
     *     limit?: int,
     *     page?: int,
     *     webspaceKey?: string,
     *     datasource?: string|null,
     * } $filters
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
        /**
         * @var array{
         *      locale?: string|null,
         *      categoryIds?: int[],
         *      categoryOperator?: 'AND'|'OR',
         *      tagNames?: string[],
         *      tagOperator?: 'AND'|'OR',
         *      templateKeys?: string[],
         *      loadGhost?: bool,
         *      limit?: int,
         *      page?: int,
         *      stage: string,
         *      webspaceKey?: string,
         *  } $filters
         */
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

        if (($page = ($filters['page'] ?? null))
            && ($limit = ($filters['limit'] ?? null))) {
            $this->dimensionContentQueryEnhancer->addPagination($queryBuilder, $page, $limit);
        }

        // TODO refactor this part to not use distinct
        // we need the distinct here, because joins due to tags/categories can lead to duplicate results
        $queryBuilder->select('DISTINCT page.uuid as id');
        $queryBuilder->addSelect('page.webspaceKey as webspace');
        $queryBuilder->addSelect('filterDimensionContent.title');

        foreach ($queryBuilder->getDQLPart('orderBy') ?? [] as $orderBy) {
            foreach ($orderBy->getParts() as $order) {
                [$column] = \explode(' ', $order);
                $queryBuilder->addSelect($column);
            }
        }

        /** @var array{id: string, title: string, webspace: string}[] $result */
        $result = $queryBuilder->getQuery()->getArrayResult();

        return $result;
    }

    protected function mapFilters(array $filters): array
    {
        if ($filters['types'] ?? null) {
            $filters['templateKeys'] = $filters['types'];
            unset($filters['types']);
        }

        return $filters;
    }

    protected function addInternalFilters(QueryBuilder $queryBuilder, array $filters, string $alias): void
    {
        if ($webspaceKey = $filters['webspaceKey'] ?? null) {
            $queryBuilder->andWhere($alias . '.webspaceKey = :webspaceKey')
                ->setParameter('webspaceKey', $webspaceKey);
        }

        if ($datasource = $filters['dataSource'] ?? null) {
            $queryBuilder->andWhere($alias . '.parent = :datasource')
                ->setParameter('datasource', $datasource);
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

        //        if ($this->hasAudienceTargeting) {
        //            $builder->enableAudienceTargeting();
        //        }

        return $builder;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getTypes(): array
    {
        $types = [];
        if ($this->tokenStorage && null !== $this->tokenStorage->getToken() && $this->formMetadataProvider) {
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
