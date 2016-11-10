<?php

/*
 * This file is part of Sulu.
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
     * @param $version
     *
     * @return mixed
     */
    public function save($tempPath, $fileName, $version);

    /**
     * Give back the path to the document.
     *
     * @param $fileName
     * @param $version
     * @param $storageOption
     *
     * @return string
     *
     * @deprecated Deprecated since 1.4, will be removed in 2.0
     */
    public function load($fileName, $version, $storageOption);

    /**
     * Returns the content for the given file as a binary string.
     *
     * @param $fileName
     * @param $version
     * @param $storageOption
     *
     * @return string
     */
    public function loadAsString($fileName, $version, $storageOption);

    /**
     * Removes the file from storage.
     *
     * @param $storageOption
     *
     * @return mixed
     */
    public function remove($storageOption);
}
