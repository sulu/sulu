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

namespace Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\SmartContent;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\Builder;
use Sulu\Bundle\AdminBundle\SmartContent\Configuration\ProviderConfigurationInterface;
use Sulu\Bundle\AdminBundle\SmartContent\SmartContentProviderInterface;
use Sulu\Bundle\ContactBundle\Admin\ContactAdmin;
use Sulu\Bundle\ContactBundle\Entity\ContactInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\ContactResourceLoader;

/**
 * @phpstan-type ContactSmartContentFilters array{
 *      page?: int,
 *      pageSize?: int|null,
 *      limit?: int|null,
 *      tagNames?: string[],
 *      categoryIds?: int[],
 *      tagOperator?: 'AND'|'OR',
 *      categoryOperator?: 'AND'|'OR',
 *  }
 */
class ContactSmartContentProvider implements SmartContentProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
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
     * @param ContactSmartContentFilters $filters
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
        $page = $filters['page'] ?? 1;
        $pageSize = $filters['pageSize'] ?? null;
        $limit = $filters['limit'] ?? null;

        $alias = 'contact';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect($alias . '.firstName as firstName');
        $queryBuilder->addSelect($alias . '.lastName as lastName');
        $queryBuilder->distinct();

        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            $sortBys,
            $alias
        );

        if (null !== $pageSize && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            $queryBuilder->setMaxResults($restLimit);
            $queryBuilder->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

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
     * @param array{
     *     tagNames?: string[],
     *     categoryIds?: int[],
     *     tagOperator?: 'AND'|'OR',
     *     categoryOperator?: 'AND'|'OR',
     * } $filters
     * @param array<string, string> $sortBys
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
        string $alias,
    ): void {
        $tagRelation = $alias . '.tags';
        $categoryRelation = $alias . '.categories';

        foreach ($sortBys as $sortBy => $sortMethod) {
            $queryBuilder->orderBy($sortBy, $sortMethod);
            $queryBuilder->addSelect($sortBy);
        }

        if (($filters['tagNames'] ?? null) && [] !== $filters['tagNames'] && ($filters['tagOperator'] ?? null)) {
            $this->addJoinFilter(
                $queryBuilder,
                $tagRelation,
                'filterTagName',
                'name',
                'tagNames',
                $filters['tagNames'],
                $filters['tagOperator'],
            );
        }

        if (($filters['categoryIds'] ?? null) && [] !== $filters['categoryIds'] && ($filters['categoryOperator'] ?? null)) {
            $this->addJoinFilter(
                $queryBuilder,
                $categoryRelation,
                'filterCategoryId',
                'id',
                'categoryIds',
                $filters['categoryIds'],
                $filters['categoryOperator'],
            );
        }
    }

    /**
     * @param int[]|string[] $parameters
     * @param 'AND'|'OR' $operator
     */
    private function addJoinFilter(
        QueryBuilder $queryBuilder,
        string $join,
        string $targetAlias,
        string $targetField,
        string $filterKey,
        array $parameters,
        string $operator = 'OR',
    ): void {
        if ('OR' === $operator) {
            $queryBuilder->leftJoin(
                $join,
                $targetAlias,
            );

            $queryBuilder->andWhere($targetAlias . '.' . $targetField . ' IN (:' . $filterKey . ')')
                ->setParameter($filterKey, $parameters);
        } elseif ('AND' === $operator) {
            foreach (\array_values($parameters) as $key => $parameter) {
                $queryBuilder->leftJoin(
                    $join,
                    $targetAlias . $key,
                );

                $queryBuilder->andWhere($targetAlias . $key . '.' . $targetField . ' = :' . $filterKey . $key)
                    ->setParameter($filterKey . $key, $parameter);
            }
        } else {
            throw new \InvalidArgumentException(
                \sprintf('The operator "%s" is not supported for this filter.', $operator),
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
