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

use Sulu\Bundle\CategoryBundle\Api\Category as CategoryWrapper;
use Sulu\Bundle\CategoryBundle\Entity\Category;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Defines the operations of the CategoryManager.
 * The CategoryManager is responsible for the centralized management of our categories.
 */
interface CategoryManagerInterface
{
    /**
     * Returns tags with a given parent and/or a given depth-level
     * if no arguments passed returns all categories.
     *
     * @param int         $parent    the id of the parent to filter for
     * @param int         $depth     the depth-level to filter for
     * @param string|null $sortBy    column name to sort the categories by
     * @param string|null $sortOrder sort order
     *
     * @return Category[]
     */
    public function find($parent = null, $depth = null, $sortBy = null, $sortOrder = null);

    /**
     * Returns the children for a given category.
     *
     * @param int         $key       the key of the category to search the children for
     * @param string|null $sortBy    column name to sort by
     * @param string|null $sortOrder sort order
     *
     * @return Category[]
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null);

    /**
     * Returns a category with a given id.
     *
     * @param int $id the id of the category
     *
     * @return Category
     */
    public function findById($id);

    /**
     * Returns a category with a given key.
     *
     * @param string $key the key of the category
     *
     * @return Category
     */
    public function findByKey($key);

    /**
     * Returns the categories with the given ids.
     *
     * @param $ids
     *
     * @return Category[]
     */
    public function findByIds(array $ids);

    /**
     * Creates a new category or overrides an existing one.
     *
     * @param array $data   The data of the category to save
     * @param int   $userId The id of the user, who is doing this change
     *
     * @return Category
     */
    public function save($data, $userId);

    /**
     * Deletes a category with a given id.
     *
     * @param int $id the id of the category to delete
     */
    public function delete($id);

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters.
     *
     * @param Category $category
     * @param string   $locale
     *
     * @return CategoryWrapper
     */
    public function getApiObject($category, $locale);

    /**
     * Same as getApiObject, but takes multiple category-entities.
     *
     * @param Category[] $categories
     * @param string     $locale
     *
     * @return CategoryWrapper[]
     */
    public function getApiObjects($categories, $locale);

    /**
     * Returns the FieldDescriptors for the categories.
     *
     * @param string $locale
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors($locale);

    /**
     * Returns the FieldDescriptor for the given key.
     *
     * @param string $locale
     * @param string $key The key of the FieldDescriptor to return
     *
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($locale, $key);
}
