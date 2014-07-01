<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Entity;

/**
 * Defines the method for the doctrine repository
 * @package Sulu\Bundle\MediaBundle\Entity
 */
interface MediaRepositoryInterface
{

    /**
     * Finds the media with a given id
     * @param $id
     * @return Media
     */
    public function findMediaById($id);

    /**
     * finds all media, can be filtered with parent
     * @param int $parent
     * @param int $depth
     * @return Media
     */
    public function findMedia($parent = null, $depth = null);
}
