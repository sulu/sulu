<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\ImageManager;

interface ImageManagerInterface {

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
} 
