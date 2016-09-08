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

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Defines the methods for the doctrine repository which enables accessing the categories.
 */
interface CategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Returns true if the given id is assigned to an existing category.
     *
     * @param $id
     *
     * @return bool
     */
    public function isCategoryId($id);

    /**
     * Returns true if the given key is assigned to an existing category.
     *
     * @param $key
     *
     * @return bool
     */
    public function isCategoryKey($key);

    /**
     * Returns the category which is assigned to the given id.
     * If no respective category is found, null is returned.
     *
     * @param int $id
     *
     * @return CategoryInterface|null
     */
    public function findCategoryById($id);

    /**
     * Returns the category which is assigned to the given key.
     * If no respective category is found, null is returned.
     *
     * @param string $key
     *
     * @return CategoryInterface|null
     */
    public function findCategoryByKey($key);

    /**
     * Finds the categories with the given ids.
     *
     * @param array $ids The ids to load
     *
     * @return CategoryInterface[]
     *
     * @deprecated Use ::findCategoriesByIds instead
     */
    public function findCategoryByIds(array $ids);

    /**
     * Returns an array of categories which are assigned to the given array of ids.
     *
     * @param array $ids
     *
     * @return CategoryInterface[]
     */
    public function findCategoriesByIds(array $ids);

    /**
     * Returns all categories. Can be filtered with parent and depth.
     *
     * @param number      $parent    the id of the parent to filter for
     * @param number      $depth     the depth-level to filter for
     * @param string|null $sortBy    column name to sort the categories by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryInterface[]
     *
     * @deprecated Use ::findChildrenCategoriesByParentId instead
     */
    public function findCategories($parent = null, $depth = null, $sortBy = null, $sortOrder = null);

    /**
     * Returns the whole category graph. Children are available through children-properties of parents.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param null $parentId
     *
     * @return CategoryInterface[]
     */
    public function findChildrenCategoriesByParentId($parentId = null);

    /**
     * Returns the children for a given category.
     *
     * @param int         $key       the key of the category to return the children for
     * @param string|null $sortBy    column name to sort by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryInterface[]
     *
     * @deprecated Use ::findChildrenCategoriesByParentKey instead
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null);

    /**
     * Returns the whole category graph. Children are available through children-properties of parents.
     * If parentKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     *
     * @param null $parentKey
     *
     * @return CategoryInterface[]
     */
    public function findChildrenCategoriesByParentKey($parentKey = null);

    /**
     * Returns an array of ids of categories which are positioned (exlusive) between a category which is assigned to
     * one of the entries of the fromIds array and a category which is assigned to one of the entries
     * of the toIds array.
     * Start- and end-points of a path are not returned.
     *
     * @param $fromIds array Start-points of the paths which are processed
     * @param $toIds array End-points of the paths which are processed
     *
     * @return array
     */
    public function findCategoryIdsBetween($fromIds, $toIds);
}
