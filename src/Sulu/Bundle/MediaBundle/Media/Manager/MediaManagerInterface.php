<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Manager;

use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\CollectionNotFoundException;
use Sulu\Bundle\MediaBundle\Media\Exception\MediaNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @method string getAdminUrl($id, $fileName, $version);
 */
interface MediaManagerInterface
{
    /**
     * Returns media with a given collection and/or ids and/or limit
     * if no arguments passed returns all media.
     *
     * @param string $locale the locale which the object will be returned
     * @param array $filter collection, ids, types
     * @param int $limit to limit the output
     * @param int $offset to offset the output
     *
     * @return Media[]
     */
    public function get($locale, $filter = [], $limit = null, $offset = null/*, $permission = null */);

    /**
     * Return the count of the last get.
     *
     * @return int
     */
    public function getCount();

    /**
     * Returns a media with a given id.
     *
     * @param int $id the id of the category
     * @param string $locale the locale which the object will be returned
     *
     * @return Media
     */
    public function getById($id, $locale);

    /**
     * Returns a media entity with a given id.
     *
     * @param int $id
     *
     * @return MediaInterface
     */
    public function getEntityById($id);

    /**
     * Returns the medias with the given ids in the specified order.
     *
     * @param string $locale
     *
     * @return Media[]
     */
    public function getByIds(array $ids, $locale/*, $permission = null */);

    /**
     * Creates a new media or overrides an existing one.
     *
     * @param UploadedFile|null $uploadedFile
     * @param array $data The data of the category to save
     * @param int|null $userId The id of the user, who is doing this change
     *
     * @return Media
     */
    public function save($uploadedFile, $data, $userId);

    /**
     * Deletes a media with a given id.
     *
     * @param int $id the id of the category to delete
     */
    public function delete($id, $checkSecurity = false);

    /**
     * Moves a media to a given collection.
     *
     * @param int $id id of media
     * @param string $locale the locale which the object will be returned
     * @param int $destCollection id of destination collection
     *
     * @return Media
     *
     * @throws MediaNotFoundException
     * @throws CollectionNotFoundException
     */
    public function move($id, $locale, $destCollection);

    /**
     * Increase the download counter of a fileVersion.
     *
     * @param int $fileVersionId
     */
    public function increaseDownloadCounter($fileVersionId);

    /**
     * Takes an array of media ids and returns an array of formats and urls.
     *
     * @param array $ids
     * @param string $locale
     *
     * @return array
     */
    public function getFormatUrls($ids, $locale);

    /**
     * Adds thumbnails and image urls.
     *
     * @return Media
     */
    public function addFormatsAndUrl(Media $media);

    /**
     * Returns download url for given id and filename.
     *
     * @param string|int $id
     * @param string $fileName
     * @param string|int $version
     *
     * @return string
     */
    public function getUrl($id, $fileName, $version);

    public function removeFileVersion(int $mediaId, int $version): void;
}
