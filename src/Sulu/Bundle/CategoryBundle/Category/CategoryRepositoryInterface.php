<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Category;

use Sulu\Bundle\CategoryBundle\Entity\Category;

/**
 * Defines the method for the doctrine repository
 * @package Sulu\Bundle\CategoryBundle\Category
 */
interface CategoryRepositoryInterface
{
    /**
     * Finds the category with a given id
     * @param int $id
     * @return Category
     */
    public function findCategoryById($id);

    /**
     * finds all categories, can be filtered with parent and depth
     * @param array $ids array of white-list of ids to filter
     * @param int $parent the id of the parent
     * @param int $depth the depth-level
     * @return Category[]
     */
    public function findCategories($ids = null, $parent = null, $depth = null);
}
