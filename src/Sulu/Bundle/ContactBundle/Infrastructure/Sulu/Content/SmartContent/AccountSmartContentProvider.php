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
use Sulu\Bundle\ContactBundle\Entity\AccountInterface;
use Sulu\Bundle\ContactBundle\Infrastructure\Sulu\Content\ResourceLoader\AccountResourceLoader;

class AccountSmartContentProvider implements SmartContentProviderInterface
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
            ->enableView(ContactAdmin::ACCOUNT_EDIT_FORM_VIEW, ['id' => 'id'])
            ->getConfiguration();
    }

    public function countBy(array $filters, array $params = []): int
    {
        $alias = 'account';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select(\sprintf('COUNT(DISTINCT %s.id)', $alias));
        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            [],
        );

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function findFlatBy(array $filters, array $sortBys, array $params = []): array
    {
        $page = $filters['page'] ?? 1;
        $pageSize = $filters['pageSize'] ?? null;
        $limit = $filters['limit'] ?? null;

        $alias = 'account';
        $queryBuilder = $this->createQueryBuilder($alias);
        $queryBuilder->select($alias . '.id as id');
        $queryBuilder->addSelect($alias . '.name as title');
        $queryBuilder->distinct();

        $this->enhanceQueryBuilder(
            $queryBuilder,
            $filters,
            $sortBys,
        );

        if (null !== $pageSize && $pageSize > 0) {
            $pageOffset = ($page - 1) * $pageSize;
            $restLimit = $limit - $pageOffset;

            $queryBuilder->setMaxResults($restLimit);
            $queryBuilder->setFirstResult($pageOffset);
        } elseif (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Resolves filter and returns id array for second query.
     *
     * @param array $filters array of filters: tags, tagOperator
     *
     * @return int[]|string[]
     */
    private function enhanceQueryBuilder(
        QueryBuilder $queryBuilder,
        array $filters,
        array $sortBys,
    ) {
        $alias = 'account';

        $tagRelation = $alias . '.tags';
        $categoryRelation = $alias . '.categories';

        foreach ($sortBys as $sortBy => $sortMethod) {
            if (!\is_string($sortBy) || !\is_string($sortMethod)) {
                continue;
            }
            $queryBuilder->orderBy($sortBy, $sortMethod);
            $queryBuilder->addSelect($sortBy);
        }

        if ($filters['tagNames'] ?? null && [] !== $filters['tagNames']) {
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

        if ($filters['categoryIds'] ?? null && [] !== $filters['categoryIds']) {
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

    public function createQueryBuilder($alias): QueryBuilder
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
