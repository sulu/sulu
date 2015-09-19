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

use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameWriteException;
use Sulu\Bundle\MediaBundle\Media\Storage\Resolver\FlysystemResolverInterface;

/**
 * Class FlysystemStorage
 * @package Sulu\Bundle\MediaBundle\Media\Storage
 */
class FlysystemStorage extends AbstractStorage
{
    const STORAGE_OPTION_FILENAME = 'fileName';
    const STORAGE_OPTION_URL = 'url';
    const STORAGE_TYPE = 'FlySystem';

    /**
     * @var string $fileSystem
     */
    protected $fileSystem;

    /**
     * @var FlysystemResolverInterface
     */
    protected $flysystemResolver;

    /**
     * @var NullLogger|LoggerInterface
     */
    protected $logger;

    /**
     * @param string $fileSystem
     * @param MountManager $mountManager
     * @param FlysystemResolverInterface $flysystemResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        $fileSystem,
        MountManager $mountManager,
        FlysystemResolverInterface $flysystemResolver,
        $logger = null
    ) {
        $this->fileSystem = $fileSystem;
        $this->mountManager = $mountManager;
        $this->flysystemResolver = $flysystemResolver;
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
        $result = $this->getFilesystem()->writeStream($fileName, $stream);
        if (!$result) {
            throw new FilenameWriteException(self::STORAGE_TYPE . ':' . $this->fileSystem .':' . $fileName);
        }

        $this->addStorageOption(self::STORAGE_OPTION_FILENAME, $fileName);
        $this->addStorageOption(
            self::STORAGE_OPTION_URL,
            $this->flysystemResolver->getUrl($this->getFilesystem(), $fileName)
        );

        return json_encode($this->storageOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function load($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        $fileName = $this->getStorageOption(self::STORAGE_OPTION_FILENAME);

        return $this->getFilesystem()->readStream($fileName);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        $fileName = $this->getStorageOption(self::STORAGE_OPTION_FILENAME);

        if ($fileName && $this->exists($fileName)) {
            $this->getFilesystem()->delete($fileName);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDownloadUrl($storageOptions)
    {
        $this->storageOptions = json_decode($storageOptions);

        return $this->getStorageOption(self::STORAGE_OPTION_URL);
    }

    /**
     * {@inheritdoc}
     */
    protected function exists($filePath)
    {
        return $this->getFilesystem()->has($filePath);
    }

    /**
     * @return FilesystemInterface
     */
    protected function getFilesystem()
    {
        return $this->mountManager->getFilesystem($this->fileSystem);
    }
}
