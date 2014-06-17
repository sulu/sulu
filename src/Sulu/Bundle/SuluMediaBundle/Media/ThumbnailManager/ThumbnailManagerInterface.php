<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ThumbnailManager;

interface ThumbnailManagerInterface {

    /**
     * Return the image by a given url
     * @param $id
     * @param $format
     * @return mixed
     */
    public function returnImage($id, $format);

    /**
     * Return media id and format
     * @param $url
     * @return array
     */
    public function getMediaProperties($url);

    /**
     * @param $fileName
     * @param $version
     * @param $storageOptions
     * @return string
     */
    public function getOriginal($fileName, $version, $storageOptions);

    /**
     * @param $id
     * @param $fileName
     * @param $storageOptions
     * @return mixed
     */
    public function getThumbNails($id, $fileName, $storageOptions);
} 
