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
    public function loadAll();

    /**
     * Loads the tag with the given id
     * @param $id number The id of the tag
     * @return Tag
     */
    public function loadById($id);

    /**
     * Loads the tag with the given name
     * @param $name
     * @return Tag
     */
    public function loadByName($name);

    /**
     * Saves the given Tag
     * @param Tag $tag The tag to save
     */
    public function save($tag);

    /**
     * Deletes the given Tag
     * @param number $id The tag to delete
     */
    public function delete($id);

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     * @param Tag $srcTag The source tag, which will be removed afterwards
     * @param Tag $destTag The destination tag, which will replace the source tag
     */
    public function merge($srcTag, $destTag);
} 
