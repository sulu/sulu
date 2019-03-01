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

use Sulu\Component\Persistence\Repository\RepositoryInterface;

/**
 * Defines the method for the doctrine repository.
 */
interface TagRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds the tag with the given ID.
     *
     * @param int $id
     *
     * @return TagInterface
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
