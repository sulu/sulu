<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Defines the operations of the CollectionManager.
 * The CollectionManager is responsible for the centralized management of our collections.
 */
namespace Sulu\Bundle\MediaBundle\Collection\Manager;

use Sulu\Bundle\MediaBundle\Entity\Collection;
use Sulu\Bundle\MediaBundle\Api\Collection as CollectionWrapper;

interface CollectionManagerInterface {

    /**
     * Returns collections with a given parent and/or a given depth-level
     * if no arguments passed returns all collection
     * @param int $parent the id of the parent to filter for
     * @param int $depth the depth-level to filter for
     * @return Collection[]
     */
    public function find($parent = null, $depth = null);

    /**
     * Returns a collection with a given id
     * @param int $id the id of the category
     * @return Collection
     */
    public function findById($id);

    /**
     * Creates a new collection or overrides an existing one
     * @param array $data The data of the category to save
     * @param int $userId The id of the user, who is doing this change
     * @return Collection
     */
    public function save($data, $userId);

    /**
     * Deletes a collection with a given id
     * @param int $id the id of the category to delete
     */
    public function delete($id);

    /**
     * Returns an API-Object for a given collection-entity. The API-Object wraps the entity
     * and provides neat getters and setters
     * @param Collection $collection
     * @param string $locale
     * @return CollectionWrapper
     */
    public function getApiObject($collection, $locale);

    /**
     * Same as getApiObject, but takes multiple collection-entities
     * @param Collection[] $collections
     * @param string $locale
     * @return CollectionWrapper[]
     */
    public function getApiObjects($collections, $locale);
} 
