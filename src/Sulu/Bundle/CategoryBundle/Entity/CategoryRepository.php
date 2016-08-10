<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Entity;

use Doctrine\ORM\Query;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sulu\Bundle\CategoryBundle\Category\CategoryRepositoryInterface;

/**
 * {@inheritdoc}
 */
class CategoryRepository extends NestedTreeRepository implements CategoryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function isCategoryId($id)
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->addSelect('1')
            ->where('category.id = :categoryId')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryId', $id);

        return !empty($query->getResult());
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoryById($id)
    {
        $queryBuilder = $this->getCategoryQuery()->where('category.id = :categoryId');
        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryId', $id);

        return $query->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoryByKey($key)
    {
        $queryBuilder = $this->getCategoryQuery()->where('category.key = :categoryKey');
        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryKey', $key);

        return $query->getOneOrNullResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoriesByIds(array $ids)
    {
        $queryBuilder = $this->getCategoryQuery();

        $queryBuilder->where($queryBuilder->expr()->in('category.id', ':ids'));
        $queryBuilder->setParameter('ids', $ids);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoriesByParentId($parentId = null)
    {
        $queryBuilder = $this->getCategoryQuery();

        if ($parentId === null) {
            $queryBuilder->andWhere('category.parent IS NULL');
        } else {
            $queryBuilder->andWhere('categoryParent.id = :parentId');
        }

        $query = $queryBuilder->getQuery();

        if ($parentId !== null) {
            $query->setParameter('parentId', $parentId);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoryIdsBetween($fromIds, $toIds)
    {
        $queryBuilder = $this->createQueryBuilder('category');
        $queryBuilder->from($this->getEntityName(), 'fromCategory');
        $queryBuilder->from($this->getEntityName(), 'toCategory');

        $queryBuilder->select('category.id');

        $queryBuilder->andWhere('fromCategory.id IN (:fromIds)');
        $queryBuilder->andWhere('toCategory.id IN (:toIds)');

        $queryBuilder->andWhere('category.lft >= fromCategory.lft');
        $queryBuilder->andWhere('category.rgt <= fromCategory.rgt');
        $queryBuilder->andWhere('category.lft <= toCategory.rgt');
        $queryBuilder->andWhere('category.rgt >= toCategory.rgt');

        $query = $queryBuilder->getQuery();
        $query->setParameter('fromIds', $fromIds);
        $query->setParameter('toIds', $toIds);

        return array_map('current', $query->getScalarResult());
    }

    /**
     * Returns the general part of the query.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getCategoryQuery()
    {
        return $this->createQueryBuilder('category')
            ->leftJoin('category.meta', 'categoryMeta')
            ->leftJoin('category.translations', 'categoryTranslations')
            ->leftJoin('category.parent', 'categoryParent')
            ->leftJoin('category.children', 'categoryChildren')
            ->addSelect('categoryMeta')
            ->addSelect('categoryTranslations')
            ->addSelect('categoryParent')
            ->addSelect('categoryChildren');
    }
}
