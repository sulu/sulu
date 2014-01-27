<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Bundle\TagBundle\Entity\Tag;

/**
 * Defines the operations of the TagManager.
 * The TagManager is responsible for the centralized management of our tags.
 * @package Sulu\Bundle\TagBundle\Tag
 */
interface TagManagerInterface
{
    /**
     * Loads all the tags managed in this system
     * @return Tag[]
     */
    public function findAll();

    /**
     * Loads the tag with the given id
     * @param $id number The id of the tag
     * @return Tag
     */
    public function findById($id);

    /**
     * Loads the tag with the given name
     * @param $name
     * @return Tag
     */
    public function findByName($name);

    /**
     * Loads the tag with the given name, or creates it, if it does not exist
     * @param string $name The name to find or create
     * @return Tag
     */
    public function findOrCreateByName($name);

    /**
     * Saves the given Tag
     * @param array $data The data of the tag to save
     * @param number|null $id The id for saving the tag (optional)
     * @return
     */
    public function save($data, $id = null);

    /**
     * Deletes the given Tag
     * @param number $id The tag to delete
     */
    public function delete($id);

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     * @param number $srcTagId The source tag, which will be removed afterwards
     * @param number $destTagId The destination tag, which will replace the source tag
     * @throws Exception\TagNotFoundException
     * @return Tag The new Tag, which is valid for both given tags
     */
    public function merge($srcTagId, $destTagId);
} 
