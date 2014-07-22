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
     * Finds the collection with a given id
     * @param int $id
     * @return Collection
     */
    public function findCollectionById($id);

    /**
     * finds all collections, can be filtered with parent and depth
     * @param int $parent the id of the parent
     * @param int $depth the depth-level
     * @return Collection[]
     */
    public function findCollections($parent = null, $depth = null);
} 
