<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\Storage;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LocalStorage implements StorageInterface
{
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
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $uploadPath,
        string $segments,
        Filesystem $filesystem,
        LoggerInterface $logger = null
    ) {
        $this->uploadPath = $uploadPath;
        $this->segments = $segments;
        $this->filesystem = $filesystem;
        $this->logger = $logger ?: new NullLogger();
    }

    public function save(string $tempPath, string $fileName, array $storageOptions = []): array
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        if (!$segment) {
            $segment = sprintf('%0' . strlen($this->segments) . 'd', rand(1, $this->segments));
        }

        $segmentPath = $this->createPath($segment);
        $fileName = $this->getUniqueFileName($segmentPath, $fileName);

        $filePath = $this->createPath($segment, $fileName);
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

        return [
            'segment' => $segment,
            'fileName' => $fileName,
        ];
    }

    public function load(array $storageOptions)
    {
        return fopen($this->getPath($storageOptions), 'r');
    }

    public function getPath(array $storageOptions): string
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        if (!$segment || !$fileName) {
            throw new \RuntimeException();
        }

        return $this->createPath($segment, $fileName);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_LOCAL;
    }

    public function remove(array $storageOptions): void
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        if (!$segment || !$fileName) {
            throw new \RuntimeException();
        }

        try {
            $this->filesystem->remove($this->uploadPath . '/' . $segment . '/' . $fileName);
        } catch (IOException $ex) {
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

        if (!$this->filesystem->exists($filePath)) {
            return $newFileName;
        }

        ++$counter;

        return $this->getUniqueFileName($folder, $fileName, $counter);
    }

    private function getPathByFolderAndFileName(string $folder, string $fileName): string
    {
        return rtrim($folder, '/') . '/' . ltrim($fileName, '/');
    }

    private function createPath(string $segment, ?string $fileName = null): string
    {
        return implode('/', array_filter([$this->uploadPath, $segment, $fileName]));
    }

    private function getStorageOption(array $storageOption, string $key): ?string
    {
        return array_key_exists($key, $storageOption) ? $storageOption[$key] : null;
    }
}
