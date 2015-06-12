<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Service;
use Sulu\Bundle\MediaBundle\Api\Media;

/**
 * Class ServiceInterface
 * @package Sulu\Bundle\MediaBundle\Media\Service
 */
interface ServiceInterface
{
    /**
     * Send add external media request
     *
     * @param Media $media
     *
     * @return boolean
     */
    public function add(Media $media);

    /**
     * Send update external media request
     *
     * @param Media $media
     *
     * @return boolean
     */
    public function update(Media $media);

    /**
     *  Send delete external media request  
     *
     * @param Media $media
     *
     * @return boolean
     */
    public function delete(Media $media);
}
