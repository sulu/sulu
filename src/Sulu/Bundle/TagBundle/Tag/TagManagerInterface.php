<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

use Sulu\Bundle\TagBundle\Entity\Tag;
use Sulu\Component\Rest\ListBuilder\FieldDescriptor\DoctrineFieldDescriptor;

/**
 * Defines the operations of the TagManager.
 * The TagManager is responsible for the centralized management of our tags.
 */
interface TagManagerInterface
{
    /**
     * Loads all the tags managed in this system.
     *
     * @return Tag[]
     */
    public function findAll();

    /**
     * Loads the tag with the given id.
     *
     * @param $id number The id of the tag
     *
     * @return Tag
     */
    public function findById($id);

    /**
     * Loads the tag with the given name.
     *
     * @param $name
     *
     * @return Tag
     */
    public function findByName($name);

    /**
     * Loads the tag with the given name, or creates it, if it does not exist.
     *
     * @param string $name   The name to find or create
     * @param int    $userId The id of the user who tries to find a tag
     *
     * @return Tag
     */
    public function findOrCreateByName($name, $userId);

    /**
     * Saves the given Tag.
     *
     * @param array       $data   The data of the tag to save
     * @param int         $userId The id of the user, who is doing this change
     * @param number|null $id     The id for saving the tag (optional)
     *
     * @return
     */
    public function save($data, $userId, $id = null);

    /**
     * Deletes the given Tag.
     *
     * @param number $id The tag to delete
     */
    public function delete($id);

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     *
     * @param array  $srcTagIds The source tags, which will be removed afterwards
     * @param number $destTagId The destination tag, which will replace the source tag
     *
     * @throws Exception\TagNotFoundException
     *
     * @return Tag The new Tag, which is valid for both given tags
     */
    public function merge($srcTagIds, $destTagId);

    /**
     * Resolves tag ids to names.
     *
     * @param $tagIds
     *
     * @return array
     */
    public function resolveTagIds($tagIds);

    /**
     * Resolves tag names to ids.
     *
     * @param $tagNames
     *
     * @return array
     */
    public function resolveTagNames($tagNames);

    /**
     * Returns the FieldDescriptors for the products.
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getFieldDescriptors();

    /**
     * Returns the FieldDescriptor for the given key.
     *
     * @param string $key The key of the FieldDescriptor to return
     *
     * @return DoctrineFieldDescriptor
     */
    public function getFieldDescriptor($key);
}
