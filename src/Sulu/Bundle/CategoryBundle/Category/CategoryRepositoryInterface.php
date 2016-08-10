<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Sulu\Bundle\CategoryBundle\Entity\Category;

/**
 * Defines the methods for the doctrine repository which enables accessing the categories.
 */
interface CategoryRepositoryInterface
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
     * Returns the category which is assigned to the given id.
     * If no respective category is found, null is returned.
     *
     * @param int $id
     *
     * @return Category|null
     */
    public function findCategoryById($id);

    /**
     * Returns the category which is assigned to the given key.
     * If no respective category is found, null is returned.
     *
     * @param string $key
     *
     * @return Category|null
     */
    public function findCategoryByKey($key);

    /**
     * Returns an array of categories which are assigned to the given array of ids.
     *
     * @param array $ids
     *
     * @return Category[]
     */
    public function findCategoriesByIds(array $ids);

    /**
     * Returns the whole category graph. Children are available through children-properties of parents.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param null $parentId
     *
     * @return Category[]
     */
    public function findCategoriesByParentId($parentId = null);

    /**
     * Returns an array of ids of categories which are positioned on a path from a category which is assigned to
     * one of the entries of the fromIds array to a category which is assigned to one of the entries
     * of the toIds array.
     * All ids of categories which are positioned on a path are returned (including start- and end-point).
     *
     * @param $fromIds array Start-points of the paths which are processed
     * @param $toIds array End-points of the paths which are processed
     *
     * @return array
     */
    public function findCategoryIdsBetween($fromIds, $toIds);
}
