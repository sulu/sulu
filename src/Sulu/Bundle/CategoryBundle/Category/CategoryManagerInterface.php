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

use Sulu\Bundle\CategoryBundle\Api\Category;
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
     * Returns the category which is assigned to the given id in the given locale.
     *
     * @param int $id
     * @param $locale
     *
     * @return Category
     *
     * @throws CategoryIdNotFoundException if the given id is not assigned to an existing category
     */
    public function findById($id, $locale);

    /**
     * Returns the category which is assigned to the given key in the given locale.
     *
     * @param string $key
     * @param $locale
     *
     * @return Category
     *
     * @throws CategoryKeyNotFoundException if the given key is not assigned to an existing category
     */
    public function findByKey($key, $locale);

    /**
     * Returns an array of categories which are assigned to the given array of ids in the given locale.
     * If an id of the array is not assigned to a category, no error is thrown.
     *
     * @param array $ids
     * @param $locale
     *
     * @return Category[]
     */
    public function findByIds(array $ids, $locale);

    /**
     * Returns the whole category graph in the given locale.
     * If parentId is set, only the sub-graph below the category which is assigned to the given id is returned.
     *
     * @param $locale
     * @param null $parentId
     *
     * @return array
     *
     * @throws CategoryIdNotFoundException if the parentId is not assigned to an existing category
     */
    public function findChildrenByParentId($locale, $parentId = null);

    /**
     * Returns the whole category graph in the given locale.
     * If parentKey is set, only the sub-graph below the category which is assigned to the given key is returned.
     *
     * @param $locale
     * @param null $parentKey
     *
     * @return array
     *
     * @throws CategoryKeyNotFoundException if the parentKey is not assigned to an existing category
     */
    public function findChildrenByParentKey($locale, $parentKey = null);

    /**
     * Creates or updates the given data as category in the given locale.
     * If data.id is set, the category which is assigned to the given id is overwritten.
     * If patch is set, the category which is assigned to the given id is updated partially.
     *
     * @param array $data
     * @param $locale
     * @param bool $patch
     *
     * @return Category
     *
     * @throws CategoryIdNotFoundException if data.id is set, but the id is not assigned to a existing category
     * @throws CategoryKeyNotUniqueException
     * @throws MissingArgumentException
     */
    public function save($data, $locale, $patch = false);

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
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category $category
     * @param string   $locale
     *
     * @return Category
     *
     * @deprecated Use the respective manager function instead
     */
    public function getApiObject($category, $locale);

    /**
     * Same as getApiObject, but takes multiple category-entities.
     *
     * @param \Sulu\Bundle\CategoryBundle\Entity\Category[] $categories
     * @param string     $locale
     *
     * @return Category
     *
     * @deprecated Use the serializer instead
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
