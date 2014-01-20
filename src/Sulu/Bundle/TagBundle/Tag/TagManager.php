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
use Sulu\Bundle\TagBundle\Entity\TagRepository;

/**
 * Responsible for centralized Tag Management
 * @package Sulu\Bundle\TagBundle\Tag
 */
class TagManager implements TagManagerInterface
{
    /**
     * The repository for communication with the database
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(TagRepositoryInterface $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Loads all the tags managed in this system
     * @return Tag[]
     */
    public function loadAll()
    {
        return $this->tagRepository->findAllTags();
    }

    /**
     * Loads the tag with the given id
     * @param $id number The id of the tag
     * @return Tag
     */
    public function loadById($id)
    {
        return $this->tagRepository->findTagById($id);
    }

    /**
     * Loads the tag with the given name
     * @param $name
     * @return Tag
     */
    public function loadByName($name)
    {
        // TODO: Implement loadByName() method.
    }

    /**
     * Saves the given Tag
     * @param Tag $tag The tag to save
     */
    public function save($tag)
    {
        // TODO: Implement save() method.
    }

    /**
     * Deletes the given Tag
     * @param Tag $tag The tag to delete
     */
    public function delete($tag)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Merges the source tag into the destination tag.
     * The source tag will be deleted.
     * @param Tag $srcTag The source tag, which will be removed afterwards
     * @param Tag $destTag The destination tag, which will replace the source tag
     */
    public function merge($srcTag, $destTag)
    {
        // TODO: Implement merge() method.
    }
}
