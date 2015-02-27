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
     * @param int $id
     * @param int $depth
     * @return Collection[]
     */
    public function findCollectionSet($id, $depth = 0);

    /**
     * Finds a collection set starting with all root nodes
     * @param int $depth
     * @return Collection[]
     */
    public function findRootCollectionSet($depth = 0);

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
     * @param string $locale
     * @return array {id: ..., title: '...'}
     */
    public function findCollectionBreadcrumbById($id, $locale);
}
