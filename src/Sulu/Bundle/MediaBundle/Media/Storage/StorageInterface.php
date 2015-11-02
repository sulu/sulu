<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

/**
 * Defines the operations of the StorageLayer.
 * The StorageLayer is a interface to centralized management of media store.
 */
interface StorageInterface
{
    /**
     * Save the document in a storage and give back the path to the document.
     *
     * @param $tempPath
     * @param $fileName
     * @param $preferredStorageOptions
     *
     * @return mixed
     */
    public function save($tempPath, $fileName, $preferredStorageOptions = null);

    /**
     * Will return a file resource.
     *
     * @param $storageOptions
     *
     * @return resource|string
     */
    public function load($storageOptions);

    /**
     * Removes the file from storage.
     *
     * @param $storageOptions
     *
     * @return mixed
     */
    public function remove($storageOptions);

    /**
     * Give back the url where the file can be downloaded
     * Null when the storage have no specific url.
     *
     * @param $storageOptions
     *
     * @return null|string
     */
    public function getDownloadUrl($storageOptions);
}
