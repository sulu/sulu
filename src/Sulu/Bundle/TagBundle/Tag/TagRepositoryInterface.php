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
 *
 * @extends RepositoryInterface<TagInterface>
 */
interface TagRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds the tag with the given ID.
     *
     * @param int $id
     *
     * @return TagInterface|null
     */
    public function findTagById($id);

    /**
     * Finds the tag with the given name.
     *
     * @param string $name
     *
     * @return TagInterface|null
     */
    public function findTagByName($name);

    /**
     * Searches for all tags.
     *
     * @deprecated use the Repository findAll method
     *
     * @return array<TagInterface>
     */
    public function findAllTags();
}
