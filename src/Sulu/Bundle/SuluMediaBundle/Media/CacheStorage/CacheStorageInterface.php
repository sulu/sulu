<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\CacheStorage;

interface CacheStorageInterface {

    /**
     * Save image and return the url to the image
     * @param $tmpPath
     * @param $id
     * @param $fileName
     * @param $options
     * @return mixed
     */
    public function save($tmpPath, $id, $fileName, $options);

    /**
     * Delete the image by the given parameters
     * @param $id
     * @param $fileName
     * @param $options
     * @return mixed
     */
    public function purge($id, $fileName, $options);

    /**
     * Return the url to an specific format of an media
     * @param $media
     * @param $format
     * @return mixed
     */
    public function getMediaUrl($media, $format);

    /**
     * Return the id and the format of a media
     * @param $url
     * @return mixed
     */
    public function analyzedMediaUrl($url);

} 
