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

/**
 * Defines the operations of the MediaManager.
 * The MediaManager is responsible for the centralized management of our media.
 * @package Sulu\Bundle\MediaBundle\Media
 */
interface MediaManagerInterface
{
    /**
     * Load the media object
     * @param $id
     * @return Media
     */
    public function get($id);

    /**
     * Loads multiple media objct
     * @param string[] $ids
     * @return Media[]
     */
    public function getMultiple($ids);
    /**
     * Adds a new file to a media
     * @param $uploadedFile
     * @param int $userId
     * @param int $collectionId
     * @param array $properties contains e.g. meta data (title, description, locale), content- and publish languages
     * @return mixed
     */
    public function add($uploadedFile, $userId, $collectionId, $properties = array());

    /**
     * Update the file to a new fileversion
     * @param $uploadedFile
     * @param int $userId
     * @param int $id
     * @param null|int $collectionId when null no changes!
     * @param array $properties
     * @return mixed
     */
    public function update($uploadedFile, $userId, $id, $collectionId = null, $properties = array());

    /**
     * Remove a media
     * @param $id
     * @param $userId
     * @return mixed
     */
    public function remove($id, $userId);
}
