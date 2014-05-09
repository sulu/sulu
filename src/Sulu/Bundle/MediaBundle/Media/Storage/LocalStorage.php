<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class LocalStorage implements StorageInterface
{
    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $version)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function load($fileName, $version, $storageOption)
    {

    }
}
