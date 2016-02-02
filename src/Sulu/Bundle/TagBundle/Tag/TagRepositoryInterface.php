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

/**
 * Defines the method for the doctrine repository.
 */
interface TagRepositoryInterface
{
    /**
     * Finds the tag with the given ID.
     *
     * @param int $id
     *
     * @return Tag
     */
    public function findTagById($id);

    /**
     * Finds the tag with the given name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function findTagByName($name);

    /**
     * Searches for all roles.
     *
     * @return array
     */
    public function findAllTags();
}
