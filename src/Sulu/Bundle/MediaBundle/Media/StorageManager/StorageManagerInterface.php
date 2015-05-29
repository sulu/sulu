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

use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

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
     * @param string $storageOption
     * @param string $storageName
     *
     * @return mixed
     */
    public function save($tempPath, $fileName, $version, $storageOption = null, $storageName = null);

    /**
     * Give back the path to the document.
     *
     * @param string $fileName
     * @param string $version
     * @param integer $storageOption
     * @param string $storageName
     *
     * @return mixed
     */
    public function load($fileName, $version, $storageOption, $storageName = null);

    /**
     * Removes the file from storage.
     *
     * @param string $storageOption
     * @param string $storageName
     *
     * @return mixed
     */
    public function remove($storageOption, $storageName = null);

    /**
     * Give back the default storage name
     *
     * @return string
     */
    public function getDefaultStorageName();

    /**
     * Adds a new Storage to the managers
     *
     * @param StorageInterface $command
     * @param $name
     *
     * @return mixed
     */
    public function add(StorageInterface $command, $name);

    /**
     * Give back Storage by a name
     *
     * @param $name
     *
     * @return mixed
     */
    public function get($name);
}
