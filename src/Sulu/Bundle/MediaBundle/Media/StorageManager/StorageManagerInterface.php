<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\StorageManager;

/**
 * Defines the operations of the StorageManager.
 * The StorageManager is a interface to centralized management of media store.
 */
interface StorageManagerInterface
{
    /**
     * Save the document in a storage and give back the path to the document.
     *
     * @param string $tempPath
     * @param string $fileName
     * @param integer $version
     * @param string $storageType
     *
     * @return mixed
     */
    public function save($tempPath, $fileName, $version, $storageType = null);

    /**
     * Give back the path to the document.
     *
     * @param string $fileName
     * @param string $version
     * @param integer $storageOption
     * @param string $storageType
     *
     * @return mixed
     */
    public function load($fileName, $version, $storageOption, $storageType = null);

    /**
     * Removes the file from storage.
     *
     * @param string $storageOption
     * @param string $storageType
     *
     * @return mixed
     */
    public function remove($storageOption, $storageType = null);
}
