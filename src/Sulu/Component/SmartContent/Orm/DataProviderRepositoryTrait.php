<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Provides basic implementation of orm DataProvider repository.
 */
trait DataProviderRepositoryTrait
{
    /**
     * @see DataProviderRepositoryInterface::findByFilters
     */
    public function findByFilters($filters, $page, $pageSize, $limit)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->addSelect('entity')
            ->where('entity.id IN (:ids)')
            ->orderBy('entity.id', 'ASC');

        $this->appendJoins($queryBuilder);

        $query = $queryBuilder->getQuery();
        $query->setParameter('ids', $this->findByFiltersIds($filters, $page, $pageSize, $limit));

        return $query->getResult();
    }

    /**
     * Resolves filter and returns id array for second query.
     *
     * @param array $filters array of filters: tags, tagOperator
     * @param int $page
     * @param int $pageSize
     * @param int $limit
     *
     * @return array
     */
    private function findByFiltersIds($filters, $page, $pageSize, $limit)
    {
        $parameter = [];

        $queryBuilder = $this->createQueryBuilder('c')
            ->select('c.id')
            ->orderBy('c.id', 'ASC');

        if (isset($filters['tags']) && count($filters['tags']) > 0) {
            $parameter = array_merge(
                $parameter,
                $this->appendTags($queryBuilder, $filters['tags'], strtolower($filters['tagOperator']))
            );
        }

        $query = $queryBuilder->getQuery();
        foreach ($parameter as $name => $value) {
            $query->setParameter($name, $value);
        }

        if ($page !== null && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            // if limitation is smaller than the page size then use the rest limit else use page size plus 1 to
            // determine has next page
            $maxResults = ($limit !== null && $pageSize > $restLimit ? $restLimit : ($pageSize + 1));

            if ($maxResults <= 0) {
                return [];
            }

            $query->setMaxResults($maxResults);
            $query->setFirstResult($pageOffset);
        } elseif ($limit !== null) {
            $query->setMaxResults($limit);
        }

        return array_map(
            function ($item) {
                return $item['id'];
            },
            $query->getScalarResult()
        );
    }

    /**
     * Append tags to query builder with given operator.
     *
     * @param QueryBuilder $queryBuilder
     * @param int[] $tags
     * @param string $operator "and" or "or"
     *
     * @return array parameter for the query.
     */
    private function appendTags(QueryBuilder $queryBuilder, $tags, $operator)
    {
        switch ($operator) {
            case 'or':
                return $this->appendTagsOr($queryBuilder, $tags);
                break;
            case 'and':
                return $this->appendTagsAnd($queryBuilder, $tags);
                break;
        }

        return [];
    }

    /**
     * Append tags to query builder with "or" operator.
     *
     * @param QueryBuilder $queryBuilder
     * @param int[] $tags
     *
     * @return array parameter for the query.
     */
    private function appendTagsOr(QueryBuilder $queryBuilder, $tags)
    {
        $queryBuilder->join('c.tags', 'tags')
            ->where('tags.id IN (:tags)');

        return ['tags' => $tags];
    }

    /**
     * Append tags to query builder with "and" operator.
     *
     * @param QueryBuilder $queryBuilder
     * @param int[] $tags
     *
     * @return array parameter for the query.
     */
    private function appendTagsAnd(QueryBuilder $queryBuilder, $tags)
    {
        $parameter = [];
        $expr = $queryBuilder->expr()->andX();

        $length = count($tags);
        for ($i = 0; $i < $length; ++$i) {
            $queryBuilder->join('c.tags', 'tags' . $i);

            $expr->add($queryBuilder->expr()->eq('tags' . $i . '.id', ':tag' . $i));

            $parameter['tag' . $i] = $tags[$i];
        }
        $queryBuilder->andWhere($expr);

        return $parameter;
    }

    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     *
     * @return QueryBuilder
     */
    abstract public function createQueryBuilder($alias);

    /**
     * Append joins to query builder for "findByFilters" function.
     *
     * @param QueryBuilder $queryBuilder
     */
    abstract protected function appendJoins(QueryBuilder $queryBuilder);
}
