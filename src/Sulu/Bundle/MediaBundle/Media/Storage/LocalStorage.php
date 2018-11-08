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

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Symfony\Component\HttpKernel\Tests\Logger;

class LocalStorage implements StorageInterface
{
    /**
     * @var string
     */
    private $storageOption = null;

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

    public function __construct(Filesystem $filesystem, int $segments, LoggerInterface $logger = null)
    {
        $this->filesystem = $filesystem;
        $this->segments = $segments;
        $this->logger = $logger ?: new NullLogger();
    }

    public function save(string $tempPath, string $fileName, int $version, string $storageOption = null): string
    {
        $this->storageOption = new \stdClass();

        if ($storageOption) {
            $oldStorageOption = json_decode($storageOption);
            $segment = $oldStorageOption->segment;
        } else {
            $segment = sprintf('%0' . strlen($this->segments) . 'd', rand(1, $this->segments));
        }

        $segmentPath = '/' . $segment;
        $fileName = $this->getUniqueFileName($segmentPath, $fileName);
        $filePath = $this->getPathByFolderAndFileName($segmentPath, $fileName);
        $this->logger->debug('Check FilePath: ' . $filePath);

        if (!$this->filesystem->has($segmentPath)) {
            $this->logger->debug('Try Create Folder: ' . $segmentPath);
            $this->filesystem->createDir($segmentPath);
        }

        $this->logger->debug('Try to copy File "' . $tempPath . '" to "' . $filePath . '"');

        try {
            $this->filesystem->writeStream($filePath, fopen($tempPath, 'r'));
        } catch (FileExistsException $exception) {
            throw new FilenameAlreadyExistsException($filePath);
        }

        $this->addStorageOption('segment', $segment);
        $this->addStorageOption('fileName', $fileName);

        return json_encode($this->storageOption);
    }

    public function load(string $fileName, int $version, string $storageOption): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'sulu-media-');

        file_put_contents($tempFile, $this->loadAsString($fileName, $version, $storageOption));

        return $fileName;
    }

    public function loadAsStream(string $fileName, int $version, string $storageOption)
    {
        $this->storageOption = json_decode($storageOption);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        $path = '/' . $segment . '/' . $fileName;

        try {
            return $this->filesystem->readStream($path);
        } catch (FileNotFoundException $exception) {
            throw new ImageProxyMediaNotFoundException(sprintf('Original media at path "%s" not found', $path));
        }
    }

    public function loadAsString(string $fileName, int $version, string $storageOption): string
    {
        return stream_get_contents($this->loadAsStream($fileName, $version, $storageOption));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $storageOption): void
    {
        $this->storageOption = json_decode($storageOption);

        $segment = $this->getStorageOption('segment');
        $fileName = $this->getStorageOption('fileName');

        if (!$segment || !$fileName) {
            return;
        }

        try {
            $this->filesystem->delete('/' . $segment . '/' . $fileName);
        } catch (FileNotFoundException $exception) {
        }
    }

    /**
     * Get a unique filename in path.
     */
    private function getUniqueFileName(string $folder, string $fileName, int $counter = 0): string
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

        if (!$this->filesystem->has($filePath)) {
            return $newFileName;
        }

        ++$counter;

        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    private function getPathByFolderAndFileName(string $folder, string $fileName): string
    {
        return rtrim($folder, '/') . '/' . ltrim($fileName, '/');
    }

    private function addStorageOption(string $key, string $value): void
    {
        $this->storageOption->$key = $value;
    }

    private function getStorageOption(string $key): ?string
    {
        return isset($this->storageOption->$key) ? $this->storageOption->$key : null;
    }
}
