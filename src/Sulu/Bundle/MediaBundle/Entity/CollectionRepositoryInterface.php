<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * Defines the method for the doctrine repository
 * @package Sulu\Bundle\MediaBundle\Entity
 */
interface CollectionRepositoryInterface
{
    /**
     * Finds a collection set starting by given ID and depth
     * @param Collection $collection
     * @param int $depth
     * @param array $filter
     * @return Collection[]
     */
    public function findCollectionSet(Collection $collection, $depth = 0, $filter = array());

    /**
     * Finds a collection set starting with all root nodes
     * @param int $offset
     * @param int $limit
     * @param string $search
     * @param int $depth
     * @return Collection[]
     */
    public function findRootCollectionSet($offset, $limit, $search, $depth = 0);

    /**
     * Finds the collection with a given id
     * @param int $id
     * @return Collection
     */
    public function findCollectionById($id);

    /**
     * finds all collections, can be filtered with parent and depth
     * @param array $filter
     * @param int $limit
     * @param int $offset
     * @param array $sortBy sort by e.g. array('title' => 'ASC')
     * @return Collection[]
     */
    public function findCollections($filter = array(), $limit = null, $offset = null, $sortBy = array());

    /**
     * Finds the breadcrumb of a collection with given id
     * @param int $id
     * @return Collection[]
     */
    public function findCollectionBreadcrumbById($id);
}
