<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Collection\Manager;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Defines the operations of the CollectionManager.
 * The CollectionManager is responsible for the centralized management of our collections.
 */
interface CollectionManagerInterface
{
    /**
     * Returns a collection with a given id.
     *
     * @param int $id the id of the collection
     * @param int $locale the locale which the collection should be return
     * @param int $depth if depth > 1 children will returned also
     * @param bool $breadcrumb if true breadcrumb will be appended
     * @param array $filter array of criteria
     * @param array $sortBy fields to sort by
     *
     * @return Collection
     */
    public function getById($id, $locale, $depth = 0, $breadcrumb = false, $filter = [], $sortBy = []);

    /**
     * Returns collections with a given parent and/or a given depth-level
     * if no arguments passed returns all collection.
     *
     * @param int $locale the locale which the collection should be return
     * @param array $filter for parent or depth
     * @param int $limit limit the output
     * @param int $offset offset the output
     * @param array $sortBy sort by e.g. array('title' => 'ASC')
     *
     * @return Paginator
     */
    public function get($locale, $filter = [], $limit = null, $offset = null, $sortBy = []);

    /**
     * Returns collection by key.
     *
     * @param string $key
     * @param string $locale
     *
     * @return Collection
     */
    public function getByKey($key, $locale);

    /**
     * Returns tree for given id. Including all parents with sliblings and children.
     *
     * @param int $id
     * @param string $locale
     *
     * @return Collection[]
     */
    public function getTreeById($id, $locale);

    /**
     * Returns collections from root with given depth.
     *
     * @param string $locale the locale which the collection should be return
     * @param int $offset
     * @param int $limit
     * @param string $search
     * @param int $depth maximum depth for query
     * @param array $sortBy
     * @param bool $systemCollections Whether or not system collectino should be included in the result
     *
     * @return \Sulu\Bundle\MediaBundle\Api\Collection[]
     */
    public function getTree($locale, $offset, $limit, $search, $depth = 0, $sortBy = [], $systemCollections = true);

    /**
     * Returns a collection count.
     *
     * @return int
     */
    public function getCount();

    /**
     * Creates a new collection or overrides an existing one.
     *
     * @param array $data The data of the category to save
     * @param int $userId The id of the user, who is doing this change
     *
     * @return Collection
     */
    public function save($data, $userId);

    /**
     * Deletes a collection with a given id.
     *
     * @param int $id the id of the category to delete
     */
    public function delete($id);

    /**
     * Moves a collection into another collection
     * If you pass parentId = null i moves to the root.
     *
     * @param int $id the id of the category to move
     * @param string $locale the locale which the collection should be return
     * @param int|null $destinationId the parent where the collection should be placed
     *
     * @return Collection
     *
     * @throws CollectionNotFoundException
     */
    public function move($id, $locale, $destinationId = null);

    /**
     * Return the FieldDescriptors.
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors();

    /**
     * Return the FieldDescriptors.
     *
     * @param string $key
     *
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($key);
}
