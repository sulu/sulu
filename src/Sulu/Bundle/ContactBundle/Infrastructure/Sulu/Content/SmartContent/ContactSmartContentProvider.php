<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentQueryEnhancer;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\ContactResourceLoader;

/**
 * @phpstan-type ContactSmartContentFilters array{
 *      categories: int[],
 *      categoryOperator: 'AND'|'OR',
 *      websiteCategories: string[],
 *      websiteCategoryOperator: 'AND'|'OR',
 *      tags: int[],
 *      tagOperator: 'AND'|'OR',
 *      websiteTags: string[],
 *      websiteTagOperator: 'AND'|'OR',
 *      types: string[],
 *      typesOperator: 'OR',
 *      locale: string,
 *      dataSource: string|null,
 *      limit: int|null,
 *      offset: int,
 *      includeSubFolders: bool,
 *      excludeDuplicates: bool,
 *  }
 * @phpstan-type ContactSmartContentCountFilters array{
 *      categories: int[],
 *      categoryOperator: 'AND'|'OR',
 *      websiteCategories: string[],
 *      websiteCategoryOperator: 'AND'|'OR',
 *      tags: int[],
 *      tagOperator: 'AND'|'OR',
 *      websiteTags: string[],
 *      websiteTagOperator: 'AND'|'OR',
 *      types: string[],
 *      typesOperator: 'OR',
 *      locale: string,
 *      dataSource: string|null,
 *      limit: int|null,
 *      includeSubFolders: bool,
 *      excludeDuplicates: bool,
 *  }
 */
readonly class ContactSmartContentProvider implements SmartContentProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
    ) {
    }

    public function getConfiguration(): ProviderConfigurationInterface
    {
        return Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableSorting(
                [
                    ['column' => 'contact.firstName', 'title' => 'sulu_contact.first_name'],
                    ['column' => 'contact.lastName', 'title' => 'sulu_contact.last_name'],
                ],
            )
            ->enableView(ContactAdmin::CONTACT_EDIT_FORM_VIEW, ['id' => 'id'])
            ->getConfiguration();
    }

    /**
     * @param ContactSmartContentCountFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        $alias = 'contact';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select(\sprintf('COUNT(DISTINCT %s.id)', $alias));
        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            [],
            $alias
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ContactSmartContentFilters $filters
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        $alias = 'contact';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect($alias . '.firstName as firstName');
        $queryBuilder->addSelect($alias . '.lastName as lastName');
        $queryBuilder->distinct();

        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);
        $this->enhanceQueryBuilder($queryBuilder, $filters, $sortBys, $alias);
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'], $filters['limit']);

        /** @var array{id: string, firstName: string, lastName: string}[] $queryResult */
        $queryResult = $queryBuilder->getQuery()->getArrayResult();

        /** @var array<array{id: string, title: string}> $result */
        $result = \array_map(
            fn (array $item) => ['id' => $item['id'], 'title' => $item['firstName'] . ' ' . $item['lastName']],
            $queryResult,
        );

        return $result;
    }

    /**
     * @param ContactSmartContentFilters|ContactSmartContentCountFilters $filters
     * @param array<string, string> $sortBys
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
        string $alias,
    ): void {
        foreach ($sortBys as $sortBy => $sortMethod) {
            $queryBuilder->orderBy($sortBy, $sortMethod);
        }

        $tagIds = $filters['tags'];
        if ([] !== $tagIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                $alias . '.tags',
                'filterTagId',
                'id',
                'tagIds',
                $tagIds,
                $filters['tagOperator'],
            );
        }

        $categoryIds = $filters['categories'];
        if ([] !== $categoryIds) {
            $this->smartContentQueryEnhancer->addJoinFilter(
                $queryBuilder,
                $alias . '.categories',
                'filterCategoryId',
                'id',
                'categoryIds',
                $categoryIds,
                $filters['categoryOperator'],
            );
        }
    }

    public function createQueryBuilder(string $alias): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->from(ContactInterface::class, $alias);
    }

    public function getType(): string
    {
        return ContactInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return ContactResourceLoader::RESOURCE_LOADER_KEY;
    }
}
