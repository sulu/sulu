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

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

/**
 * Class S3Storage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class S3Storage implements StorageInterface
{
    /**
     * @var string
     */
    private $storageOption = null;

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $version)
    {
        $this->storageOption = new \stdClass();



        return json_encode($this->storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function load($fileName, $version, $storageOption)
    {
        // TODO: Implement load() method.
    }

    /**
     * Removes the file from storage
     * @param $storageOption
     * @return mixed
     */
    public function remove($storageOption)
    {
        // TODO: Implement remove() method.
    }

    /**
     * @param $key
     * @param $value
     */
    private function addStorageOption($key, $value)
    {
        $this->storageOption->$key = $value;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getStorageOption($key)
    {
        return isset($this->storageOption->$key) ? $this->storageOption->$key : null;
    }
}
