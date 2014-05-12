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

use \stdClass;

class LocalStorage implements StorageInterface
{
    private $storageOption = null;

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $version, $storageOption = null)
    {
        $this->storageOption = new stdClass();

        if ($storageOption) {
            $oldStorageOption = json_decode($storageOption);
            $segment = $oldStorageOption->segment;
        } else {
            $segment = rand(1, $this->getConfigParameter('segments'));
        }

        $uploadPath = $this->getConfigParameter('uploadPath');
        $fileName = $this->getUniqueFileName($uploadPath . '/' . $segment , $fileName);

        copy($tempPath, $uploadPath . '/' . $segment . '/' . $fileName);

        $this->addStorageOption('segment', $segment);
        $this->addStorageOption('fileName', $fileName);

        return json_encode($this->storageOption);
    }

    /**
     * {@inheritdoc}
     */
    public function load($fileName, $version, $storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $uploadPath = $this->getConfigParameter('uploadPath');
        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        return $uploadPath . '/' . $segment . '/' . $fileName;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $uploadPath = $this->getConfigParameter('uploadPath');

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        unlink($uploadPath . '/' . $segment . '/' . $fileName);
    }

    /**
     * get a unique filename in path
     * @param $folder
     * @param $fileName
     * @param int $counter
     * @return string
     */
    private function getUniqueFileName($folder, $fileName, $counter = 0)
    {
        $newFileName = $fileName;

        if ($counter > 0) {
            $fileNameParts = explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter . '.' . $fileNameParts[1];
        }

        $filePath = $folder . $newFileName;

        if (!file_exists($filePath)) {
            return $newFileName;
        }

        $counter++;
        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    /**
     * give back a config parameter
     * @param $key
     * @return int|null|string
     */
    private function getConfigParameter($key)
    {
        $value = null;

        switch ($key) {
            case 'segments':
                $value = 10; // TODO from config
                break;
            case 'uploadPath':
                $value = '/uploads/sulumedia'; // TODO from config
                break;
        }

        return $value;
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
     */
    private function getStorageOption($key)
    {
        return $this->storageOption->$key;
    }
}
