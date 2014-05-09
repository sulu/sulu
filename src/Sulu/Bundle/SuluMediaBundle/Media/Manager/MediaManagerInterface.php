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

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Defines the operations of the MediaManager.
 * The MediaManager is responsible for the centralized management of our medias.
 * @package Sulu\Bundle\MediaBundle\Media
 */
interface MediaManagerInterface
{
    /**
     * Adds a new file to a media
     * @param UploadedFile $file
     * @param $userId
     * @param $collectionId
     * @param array $properties contains e.g. meta data (title, description, locale), content- and publish languages
     * @return mixed
     */
    public function add(UploadedFile $file, $userId, $collectionId, $properties = array());

    /**
     * Update the file to a new fileversion
     * @param UploadedFile $file
     * @param $userId
     * @param $id
     * @param null $collectionId when null no changes!
     * @param array $properties
     * @return mixed
     */
    public function update(UploadedFile $file, $userId, $id, $collectionId = null, $properties = array());

    /**
     * Remove a media
     * @param $id
     * @param $userId
     * @return mixed
     */
    public function remove($id, $userId);
}
