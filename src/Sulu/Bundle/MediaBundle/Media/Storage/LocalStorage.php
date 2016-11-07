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

use stdClass;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Log\NullLogger;
use Symfony\Component\HttpKernel\Tests\Logger;

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
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var NullLogger|Logger
     */
    protected $logger;

    public function __construct($uploadPath, $segments, Filesystem $filesystem, $logger = null)
    {
        $this->uploadPath = $uploadPath;
        $this->segments = $segments;
        $this->filesystem = $filesystem;
        $this->logger = $logger ?: new NullLogger();
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
            $segment = sprintf('%0' . strlen($this->segments) . 'd', rand(1, $this->segments));
        }

        $segmentPath = $this->uploadPath . '/' . $segment;
        $fileName = $this->getUniqueFileName($segmentPath, $fileName);
        $filePath = $this->getPathByFolderAndFileName($segmentPath, $fileName);
        $this->logger->debug('Check FilePath: ' . $filePath);

        if (!$this->filesystem->exists($segmentPath)) {
            $this->logger->debug('Try Create Folder: ' . $segmentPath);
            $this->filesystem->mkdir($segmentPath, 0777);
        }

        $this->logger->debug('Try to copy File "' . $tempPath . '" to "' . $filePath . '"');
        if ($this->filesystem->exists($filePath)) {
            throw new FilenameAlreadyExistsException($filePath);
        }
        $this->filesystem->copy($tempPath, $filePath);

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
    public function loadAsString($fileName, $version, $storageOption)
    {
        $path = $this->load($fileName, $version, $storageOption);

        if (!$path || !file_exists($path)) {
            throw new ImageProxyMediaNotFoundException(sprintf('Original media at path "%s" not found', $path));
        }

        return file_get_contents($path);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if (!$segment || !$fileName) {
            return false;
        }

        try {
            $this->filesystem->remove($this->uploadPath . '/' . $segment . '/' . $fileName);
        } catch (IOException $ex) {
            return false;
        }

        return true;
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
    private function getUniqueFileName($folder, $fileName, $counter = 0)
    {
        $newFileName = $fileName;

        if ($counter > 0) {
            $fileNameParts = explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter;

            if (isset($fileNameParts[1])) {
                $newFileName .= '.' . $fileNameParts[1];
            }
        }

        $filePath = $this->getPathByFolderAndFileName($folder, $newFileName);

        $this->logger->debug('Check FilePath: ' . $filePath);

        if (!$this->filesystem->exists($filePath)) {
            return $newFileName;
        }

        ++$counter;

        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    /**
     * @param $folder
     * @param $fileName
     *
     * @return string
     */
    private function getPathByFolderAndFileName($folder, $fileName)
    {
        return rtrim($folder, '/') . '/' . ltrim($fileName, '/');
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
     *
     * @return mixed
     */
    private function getStorageOption($key)
    {
        return isset($this->storageOption->$key) ? $this->storageOption->$key : null;
    }
}
