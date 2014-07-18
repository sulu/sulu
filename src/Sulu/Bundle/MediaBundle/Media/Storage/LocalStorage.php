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
    /**
     * @var string
     */
    private $storageOption = null;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var int
     */
    private $segments;

    /**
     * @param string $uploadPath
     * @param int $segments
     */
    public function __construct($uploadPath, $segments)
    {
        $this->uploadPath = $uploadPath;
        $this->segments = $segments;
    }

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
            $segment = rand(1, $this->segments);
        }

        $segmentPath = $this->uploadPath . '/' . $segment;
        $fileName = $this->getUniqueFileName($segmentPath , $fileName);

        if (!file_exists($segmentPath)) {
            mkdir($segmentPath, 0777, true);
        }
        copy($tempPath, $segmentPath . '/' . $fileName);

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

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if ($segment && $fileName) {
            return $this->uploadPath . '/' . $segment . '/' . $fileName;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if ($segment && $fileName) {
            @unlink($this->uploadPath . '/' . $segment . '/' . $fileName);
            return true;
        } else {
            return false;
        }
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
