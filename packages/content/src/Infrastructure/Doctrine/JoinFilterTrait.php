<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Infrastructure\Doctrine;

use Doctrine\ORM\QueryBuilder;

trait JoinFilterTrait
{
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
}
