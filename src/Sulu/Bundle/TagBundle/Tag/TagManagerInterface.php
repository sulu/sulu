<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag;

/**
 * Defines the operations of the TagManager.
 * The TagManager is responsible for the centralized management of our tags.
 */
interface TagManagerInterface
{
    /**
     * Loads all the tags managed in this system.
     *
     * @deprecated use the Repository findAll method
     *
     * @return TagInterface[]
     */
    public function findAll();

    /**
     * Loads the tag with the given id.
     *
     * @param $id number The id of the tag
     *
     * @return TagInterface
     */
    public function findById($id);

    /**
     * Loads the tag with the given name.
     *
     * @return TagInterface
     */
    public function findByName($name);

    /**
     * Loads the tag with the given name, or creates it, if it does not exist.
     *
     * @param string $name The name to find or create
     *
     * @return TagInterface
     */
    public function findOrCreateByName($name);

    /**
     * Saves the given Tag.
     *
     * @param array $data The data of the tag to save
     * @param number|null $id The id for saving the tag (optional)
     */
    public function save($data, $id = null);

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
     * @param array $srcTagIds The source tags, which will be removed afterwards
     * @param number $destTagId The destination tag, which will replace the source tag
     *
     * @return TagInterface The new Tag, which is valid for both given tags
     *
     * @throws Exception\TagNotFoundException
     */
    public function merge($srcTagIds, $destTagId);

    /**
     * Resolves tag ids to names.
     *
     * @return array
     */
    public function resolveTagIds($tagIds);

    /**
     * Resolves tag names to ids.
     *
     * @return array
     */
    public function resolveTagNames($tagNames);
}
