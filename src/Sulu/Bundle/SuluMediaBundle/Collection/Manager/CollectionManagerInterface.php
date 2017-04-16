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

use Sulu\Bundle\MediaBundle\Api\Collection;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

interface CollectionManagerInterface
{
    /**
     * Returns collections with a given parent and/or a given depth-level
     * if no arguments passed returns all collection
     * @param int $id the id of the collection
     * @param int $locale the locale which the collection should be return
     * @return Collection[]
     */
    public function getById($id, $locale);

    /**
     * Returns a collection with a given id
     * @param int $locale the locale which the collection should be return
     * @param int $parent the parent id to filter for
     * @param int $depth the depth to filter for
     * @return Collection
     */
    public function get($locale, $parent = null, $depth = null);

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
     * Return the FieldDescriptors
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors();

    /**
     * Return the FieldDescriptors
     * @param string $key
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($key);
} 
