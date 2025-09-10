<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Sulu\Component\Persistence\Repository\RepositoryInterface;
use Sulu\Component\Security\Authentication\UserInterface;

/**
 * Defines the method for the doctrine repository.
 *
 * @extends RepositoryInterface<MediaInterface>
 */
interface MediaRepositoryInterface extends RepositoryInterface
{
    /**
     * Finds the media with a given id.
     *
     * @param int $id
     *
     * @return MediaInterface|null
     */
    public function findMediaById($id);

    /**
     * Finds the media with a given id, with just enough information
     * to be able to render the actual media.
     *
     * @param int $id
     * @param string|null $formatKey
     *
     * @return MediaInterface|null
     */
    public function findMediaByIdForRendering($id, $formatKey /*, int<1, max>|null $version = null */);

    /**
     * Finds all media, can be filtered with parent.
     *
     * @param array $filter
     * @param int $limit
     * @param int $offset
     * @param int|null $permission
     *
     * @return MediaInterface[]
     */
    public function findMedia(
        $filter = [],
        $limit = null,
        $offset = null,
        ?UserInterface $user = null,
        $permission = null
    );

    /**
     * Finds all the information needed to generate an url
     * to the media and to display the media. The method finds
     * the information for all medias with given ids.
     *
     * @param array $ids array The ids of the medias for which the info should be found
     * @param string $locale string The locale in which the display info should be loaded
     *
     * @return array
     */
    public function findMediaDisplayInfo($ids, $locale);

    /**
     * Returns the most recent version of a media for the specified
     * filename within a collection.
     *
     * @param string $filename
     * @param int $collectionId
     *
     * @return MediaInterface|null
     */
    public function findMediaWithFilenameInCollectionWithId($filename, $collectionId);

    /**
     * @param int $collectionId
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array{media: iterable<MediaInterface>, count: int}
     */
    public function findMediaByCollectionId($collectionId, $limit, $offset);

    /**
     * Returns amount of affected rows.
     *
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function count(array $filter): int;

    /**
     * @return array<array{id: int, resourceKey: string, depth: int}>
     */
    public function findMediaResourcesByCollection(int $collectionId, bool $includeDescendantCollections = true): array;
}
