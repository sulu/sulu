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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\AccountResourceLoader;
use Sulu\Content\Application\DeduplicationStore\DeduplicationStoreInterface;

/**
 * @phpstan-type AccountSmartContentFilters array{
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
 *      excluded?: string[],
 *  }
 * @phpstan-type AccountSmartContentCountFilters array{
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
 *      excluded?: string[],
 *  }
 */
readonly class AccountSmartContentProvider implements SmartContentProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SmartContentQueryEnhancer $smartContentQueryEnhancer,
        private ?DeduplicationStoreInterface $deduplicationStore = null,
    ) {
        if (null === $this->deduplicationStore) {
            @trigger_deprecation('sulu/sulu', '3.0.12', 'Instantiating AccountSmartContentProvider without the $deduplicationStore argument is deprecated.');
        }
    }

    public function getConfiguration(): ProviderConfigurationInterface
    {
        return Builder::create()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableView(ContactAdmin::ACCOUNT_EDIT_FORM_VIEW, ['id' => 'id'])
            ->getConfiguration();
    }

    /**
     * @param AccountSmartContentCountFilters $filters
     */
    public function countBy(array $filters, array $params = []): int
    {
        $alias = 'account';
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
     * @param AccountSmartContentFilters $filters
     */
    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        $alias = 'account';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect($alias . '.name as title');
        $queryBuilder->distinct();

        $this->smartContentQueryEnhancer->addOrderBySelects($queryBuilder);
        $this->enhanceQueryBuilder($queryBuilder, $filters, $sortBys, $alias);
        $this->smartContentQueryEnhancer->addPagination($queryBuilder, $filters['offset'], $filters['limit']);

        /** @var array<array{id: string, title: string}> $result */
        $result = $queryBuilder->getQuery()->getArrayResult();

        foreach ($result as $item) {
            $this->deduplicationStore?->add((string) $item['id'], AccountInterface::RESOURCE_KEY);
        }

        return $result;
    }

    /**
     * @param AccountSmartContentFilters|AccountSmartContentCountFilters $filters
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

        $excluded = $filters['excluded'] ?? [];
        if ($filters['excludeDuplicates']) {
            $excluded = \array_values(\array_unique([...$excluded, ...($this->deduplicationStore?->getIds(AccountInterface::RESOURCE_KEY) ?? [])]));
        }
        if ([] !== $excluded) {
            $queryBuilder->andWhere($alias . '.id NOT IN (:excluded)')
                ->setParameter('excluded', $excluded);
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
            ->from(AccountInterface::class, $alias);
    }

    public function getType(): string
    {
        return AccountInterface::RESOURCE_KEY;
    }

    public function getResourceLoaderKey(): string
    {
        return AccountResourceLoader::RESOURCE_LOADER_KEY;
    }
}
