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

/**
 * {@inheritdoc}
 */
class CategoryRepository extends NestedTreeRepository implements CategoryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createNew()
    {
        $className = $this->getClassName();

        return new $className();
    }

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
    public function isCategoryKey($key)
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->addSelect('1')
            ->where('category.key = :categoryKey')
            ->setMaxResults(1);

        $query = $queryBuilder->getQuery();
        $query->setParameter('categoryKey', $key);

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
    public function findChildrenCategoriesByParentId($parentId = null)
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
    public function findChildrenCategoriesByParentKey($parentKey = null)
    {
        $queryBuilder = $this->getCategoryQuery();

        if ($parentKey === null) {
            $queryBuilder->andWhere('category.parent IS NULL');
        } else {
            $queryBuilder->andWhere('categoryParent.key = :parentKey');
        }

        $query = $queryBuilder->getQuery();

        if ($parentKey !== null) {
            $query->setParameter('parentKey', $parentKey);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findCategoryIdsBetween($fromIds, $toIds)
    {
        $fromIds = array_filter($fromIds);
        $toIds = array_filter($toIds);

        $queryBuilder = $this->createQueryBuilder('category');
        if ($fromIds) {
            $queryBuilder->from($this->getEntityName(), 'fromCategory');
        }
        if ($toIds) {
            $queryBuilder->from($this->getEntityName(), 'toCategory');
        }

        $queryBuilder->select('category.id');

        if ($fromIds) {
            $queryBuilder->andWhere('fromCategory.id IN (:fromIds)');
            $queryBuilder->andWhere('category.lft > fromCategory.lft');
            $queryBuilder->andWhere('category.rgt < fromCategory.rgt');
        }
        if ($toIds) {
            $queryBuilder->andWhere('toCategory.id IN (:toIds)');
            $queryBuilder->andWhere('category.lft < toCategory.rgt');
            $queryBuilder->andWhere('category.rgt > toCategory.rgt');
        }

        $query = $queryBuilder->getQuery();
        if ($fromIds) {
            $query->setParameter('fromIds', $fromIds);
        }
        if ($toIds) {
            $query->setParameter('toIds', $toIds);
        }

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

    /**
     * {@inheritdoc}
     */
    public function findCategoryByIds(array $ids)
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findCategoriesByIds() instead.', E_USER_DEPRECATED);

        return $this->findCategoriesByIds($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function findCategories($parent = null, $depth = null, $sortBy = null, $sortOrder = null)
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenCategoriesByParentId() instead.', E_USER_DEPRECATED);

        $queryBuilder = $this->getCategoryQuery();
        $queryBuilder->andWhere('category.parent IS NULL');

        if ($parent !== null) {
            $queryBuilder->andWhere('categoryParent.id = :parentId');
        }
        if ($depth !== null) {
            $queryBuilder->andWhere('category.depth = :depth');
        }

        if ($sortBy) {
            $sortOrder = ($sortOrder) ? $sortOrder : 'asc';
            $queryBuilder->addOrderBy('category.' . $sortBy, $sortOrder);
        }

        $query = $queryBuilder->getQuery();
        if ($parent !== null) {
            $query->setParameter('parentId', $parent);
        }
        if ($depth !== null) {
            $query->setParameter('depth', $depth);
        }

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null)
    {
        @trigger_error(__METHOD__ . '() is deprecated since version 1.4 and will be removed in 2.0. Use findChildrenCategoriesByParentKey() instead.', E_USER_DEPRECATED);

        $queryBuilder = $this->getCategoryQuery()
            ->from('SuluCategoryBundle:Category', 'parent')
            ->andWhere('parent.key = :key')
            ->andWhere('category.parent = parent');

        if ($sortBy) {
            $sortOrder = ($sortOrder) ? $sortOrder : 'asc';
            $queryBuilder->addOrderBy('category.' . $sortBy, $sortOrder);
        }

        $query = $queryBuilder->getQuery();
        $query->setParameter('key', $key);

        return $query->getResult();
    }
}
