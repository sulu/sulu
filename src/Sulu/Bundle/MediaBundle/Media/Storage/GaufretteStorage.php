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

use Gaufrette\Filesystem;
use Gaufrette\FilesystemMap;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameWriteException;

/**
 * Class GaufretteStorage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class GaufretteStorage extends AbstractStorage
{
    const STORAGE_TYPE = 'Gaufrette';
    const STORAGE_OPTION_FILENAME = 'fileName';

    /**
     * @var string $fileSystem
     */
    protected $fileSystem;

    /**
     * @var FilesystemMap
     */
    protected $filesystemMap;

    /**
     * @var NullLogger|LoggerInterface
     */
    protected $logger;

    /**
     * @param string $fileSystem
     * @param FilesystemMap $filesystemMap
     * @param LoggerInterface $logger
     */
    public function __construct(
        $fileSystem,
        FilesystemMap $filesystemMap,
        $logger = null
    ) {
        throw new \Exception('Not implemented yet');

        $this->fileSystem = $fileSystem;
        $this->filesystemMap = $filesystemMap;
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function save($tempPath, $fileName, $storageOptions = null)
    {
        $this->storageOptions = new \stdClass();
        $fileName = $this->getUniqueFileName('', $fileName);

        if ($this->exists($fileName)) {
            throw new FilenameAlreadyExistsException(self::STORAGE_TYPE . ':' . $this->fileSystem .':' . $fileName);
        }

        $stream = fopen($tempPath, 'r+');
        // TODO $result = $this->getFilesystem()->writeStream($fileName, $stream);
        if (!$result) {
            throw new FilenameWriteException(self::STORAGE_TYPE . ':' . $this->fileSystem .':' . $fileName);
        }

        $this->addStorageOption(self::STORAGE_OPTION_FILENAME, $fileName);

        return json_encode($this->storageOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function load($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        $fileName = $this->getStorageOption(self::STORAGE_OPTION_FILENAME);

        // return $this->getFilesystem()->readStream($fileName); TODO
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        // TODO return $this->getFilesystem()->delete($this->getStorageOption(self::STORAGE_OPTION_FILENAME));
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadUrl($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        // TODO

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function exists($filePath)
    {
        return $this->getFilesystem()->has($filePath);
    }

    /**
     * @return Filesystem
     */
    protected function getFileSystem()
    {
        $this->filesystemMap->get($this->fileSystem);
    }
}
