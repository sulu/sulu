<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Defines the method for the doctrine repository.
 */
interface MediaRepositoryInterface
{
    /**
     * Finds the media with a given id.
     *
     * @param $id
     *
     * @return Media
     */
    public function findMediaById($id);

    /**
     * finds all media, can be filtered with parent.
     *
     * @param array $filter
     * @param int $limit
     * @param int $offset
     * @param UserInterface $user
     * @param null $permission
     *
     * @return Paginator
     */
    public function findMedia(
        $filter = [],
        $limit = null,
        $offset = null,
        UserInterface $user = null,
        $permission = null
    );

    /**
     * @param string $filename
     * @param int $collectionId
     *
     * @return Media
     */
    public function findMediaWithFilenameInCollectionWithId($filename, $collectionId);

    /**
     * @param $collectionId
     * @param $limit
     * @param $offset
     *
     * @return mixed
     */
    public function findMediaByCollectionId($collectionId, $limit, $offset);

    /**
     * Returns amount of affected rows.
     *
     * @param array $filter
     *
     * @return int
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function count(array $filter);
}
