<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\SmartContent;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

class SmartContentQueryEnhancer
{
    public function addPagination(QueryBuilder $queryBuilder, int $page, ?int $limit, ?int $pageSize): void
    {
        if (null !== $pageSize && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;

            if (null !== $limit) {
                // remaining items after offset
                $remainingItems = \max(0, $limit - $pageOffset);
                $restLimit = \min($pageSize, $remainingItems);
            } else {
                $restLimit = $pageSize;
            }

            $queryBuilder->setMaxResults($restLimit);
            $queryBuilder->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }
    }

    /**
     * If you use distinct in your query, you also need to select all columns used in the orderBy clause.
     */
    public function addOrderBySelects(QueryBuilder $queryBuilder): void
    {
        /** @var OrderBy[]|null $queryParts */
        $queryParts = $queryBuilder->getDQLPart('orderBy');
        foreach ($queryParts ?? [] as $orderBy) {
            foreach ($orderBy->getParts() as $order) {
                [$column] = \explode(' ', $order);
                $queryBuilder->addSelect($column);
            }
        }
    }

    /**
     * @param int[]|string[] $parameters
     * @param 'AND'|'OR' $operator
     */
    public function addJoinFilter(
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
}
