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

/**
 * Class AbstractStorage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
abstract class AbstractStorage implements StorageInterface
{
    /**
     * @var \stdClass
     */
    protected $storageOptions;

    /**
     * Add a value to the current storageOptions
     *
     * @param $key
     * @param $value
     */
    protected function addStorageOption($key, $value)
    {
        $this->storageOptions->$key = $value;
    }

    /**
     * Returns the a value from the current storageOption
     *
     * @param $key
     *
     * @return mixed
     */
    protected function getStorageOption($key)
    {
        return isset($this->storageOptions->$key) ? $this->storageOptions->$key : null;
    }

    /**
     * get a unique filename in path.
     *
     * @param $folder
     * @param $fileName
     * @param int $counter
     *
     * @return string
     */
    protected function getUniqueFileName($folder, $fileName, $counter = 0)
    {
        $newFileName = $fileName;

        if ($counter > 0) {
            $fileNameParts = explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter . '.' . $fileNameParts[1];
        }

        $filePath = $this->getPathByFolderAndFileName($folder, $newFileName);

        if (!$this->exists($filePath)) {
            return $newFileName;
        }

        ++$counter;

        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    /**
     * Return if a file exists in the storage
     *
     * @param $filePath
     * @return bool
     */
    protected abstract function exists($filePath);

    /**
     * @param $folder
     * @param $fileName
     *
     * @return string
     */
    protected function getPathByFolderAndFileName($folder, $fileName)
    {
        if (!empty($folder)) {
            $folder = rtrim($folder, '/') . '/';
        }

        return $folder . ltrim($fileName, '/');
    }
}