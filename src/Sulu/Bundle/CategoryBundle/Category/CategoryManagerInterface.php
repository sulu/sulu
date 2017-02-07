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

use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotUniqueException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Defines the operations of the CategoryManager.
 * The CategoryManager is responsible for the centralized management of our categories.
 */
interface CategoryManagerInterface
{
    /**
     * Returns the category which is assigned to the given id.
     *
     * @param int $id
     *
     * @return CategoryInterface
     *
     * @throws CategoryIdNotFoundException if the given id is not assigned to an existing category
     */
    public function findById($id);

    /**
     * Returns the category which is assigned to the given key.
     *
     * @param string $key
     *
     * @return CategoryInterface
     *
     * @throws CategoryKeyNotFoundException if the given key is not assigned to an existing category
     */
    public function findByKey($key);

    /**
     * Returns an array of categories which are assigned to the given array of ids.
     * If an id of the array is not assigned to a category, no error is thrown.
     *
     * @param array $ids
     *
     * @return CategoryInterface[]
     */
    public function findByIds(array $ids);

    /**
     * Returns tags with a given parent and/or a given depth-level
     * if no arguments passed returns all categories.
     *
     * @param int         $parent    the id of the parent to filter for
     * @param int         $depth     the depth-level to filter for
     * @param string|null $sortBy    column name to sort the categories by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryInterface[]
     *
     * @deprecated Use ::findChildrenByParentId instead
     */
    public function find($parent = null, $depth = null, $sortBy = null, $sortOrder = null);

    /**
     * Returns the whole category graph.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param null $parentId
     *
     * @return array
     *
     * @throws CategoryIdNotFoundException if the parentId is not assigned to an existing category
     */
    public function findChildrenByParentId($parentId = null);

    /**
     * Returns the children for a given category.
     *
     * @param int         $key       the key of the category to search the children for
     * @param string|null $sortBy    column name to sort by
     * @param string|null $sortOrder sort order
     *
     * @return CategoryInterface[]
     *
     * @deprecated Use ::findChildrenByParentKey instead
     */
    public function findChildren($key, $sortBy = null, $sortOrder = null);

    /**
     * Returns the whole category graph.
     * If parentKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     *
     * @param null $parentKey
     *
     * @return array
     *
     * @throws CategoryKeyNotFoundException if the parentKey is not assigned to an existing category
     */
    public function findChildrenByParentKey($parentKey = null);

    /**
     * Creates or updates the given data as category in the given locale and return the saved category.
     * If data.id is set, the category which is assigned to the given id is overwritten.
     * If patch is set, the category which is assigned to the given id is updated partially.
     *
     * @param $data
     * @param $userId int Id of the user which is set as creator/changer. If null, the user of the request is set
     * @param $locale
     * @param bool $patch
     *
     * @return CategoryInterface
     *
     * @throws CategoryIdNotFoundException if data.id is set, but the id is not assigned to a existing category
     * @throws CategoryKeyNotUniqueException
     * @throws MissingArgumentException
     */
    public function save($data, $userId, $locale, $patch = false);

    /**
     * Deletes the category which is assigned to the given id.
     *
     * @param int $id
     *
     * @throws CategoryIdNotFoundException if the given id is not assigned to an existing category
     */
    public function delete($id);

    /**
     * Returns an API-Object for a given category-entity. The API-Object wraps the entity
     * and provides neat getters and setters.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryInterface $category
     * @param string   $locale
     *
     * @return CategoryInterface
     */
    public function getApiObject($category, $locale);

    /**
     * Same as getApiObject, but takes multiple category-entities.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\CategoryInterface[] $categories
     * @param string     $locale
     *
     * @return CategoryInterface
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
