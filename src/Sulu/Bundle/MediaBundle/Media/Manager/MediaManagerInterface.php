<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Sulu\Bundle\MediaBundle\Entity\Media;
use Sulu\Bundle\MediaBundle\Api\Media as MediaWrapper;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface MediaManagerInterface {

    /**
     * Returns media with a given collection and/or ids and/or limit
     * if no arguments passed returns all media
     * @param int $collection the id of the collection to filter for
     * @param array $ids the media ids
     * @param int $limit to limite the output
     * @return Media[]
     */
    public function find($collection = null, $ids = null, $limit = null);

    /**
     * Returns a media with a given id
     * @param int $id the id of the category
     * @return Media
     */
    public function findById($id);

    /**
     * Creates a new media or overrides an existing one
     * @param UploadedFile $uploadedFile
     * @param array $data The data of the category to save
     * @param int $userId The id of the user, who is doing this change
     * @return Media
     */
    public function save($uploadedFile, $data, $userId);

    /**
     * Deletes a media with a given id
     * @param int $id the id of the category to delete
     */
    public function delete($id);

    /**
     * Returns an API-Object for a given collection-entity. The API-Object wraps the entity
     * and provides neat getters and setters
     * @param Media $media
     * @param string $locale
     * @return MediaWrapper
     */
    public function getApiObject($media, $locale);

    /**
     * Same as getApiObject, but takes multiple media-entities
     * @param Media[] $media
     * @param string $locale
     * @return MediaWrapper[]
     */
    public function getApiObjects($media, $locale);
}
