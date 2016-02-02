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
 * Defines the method for the doctrine repository.
 */
interface CategoryRepositoryInterface
{
    /**
     * Finds the category with a given id.
     *
     * @param int $id
     *
     * @return Category
     */
    public function findCategoryById($id);

    /**
     * Finds the category with a given key.
     *
     * @param string $key
     *
     * @return Category
     */
    public function findCategoryByKey($key);

    /**
     * Finds the categories with the given ids.
     *
     * @param array $ids The ids to load
     *
     * @return Category[]
     */
    public function findCategoryByIds(array $ids);

    /**
     * Returns all categories. Can be filtered with parent and depth.
     *
     * @param number      $parent    the id of the parent to filter for
     * @param number      $depth     the depth-level to filter for
     * @param string|null $sortBy    column name to sort the categories by
     * @param string|null $sortOrder sort order
     *
     * @return mixed|null
     */
    public function findCategories($parent = null, $depth = null, $sortBy = null, $sortOrder = null);

    /**
     * Returns the children for a given category.
     *
     * @param int         $key       the key of the category to return the children for
     * @param string|null $sortBy    column name to sort by
     * @param string|null $sortOrder sort order
     *
     * @return Category[]
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null);
}
