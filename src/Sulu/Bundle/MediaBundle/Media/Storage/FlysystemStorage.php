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

use League\Flysystem\AdapterInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Symfony\Component\Filesystem\Exception\IOException;

abstract class FlysystemStorage implements StorageInterface
{
    public function __construct(private FilesystemInterface $filesystem, private int $segments)
    {
    }

    public function save(string $tempPath, string $fileName, array $storageOptions = []): array
    {
        if (!\array_key_exists('segment', $storageOptions)) {
            $storageOptions['segment'] = \sprintf('%0' . \strlen($this->segments) . 'd', \rand(1, $this->segments));
        }

        $this->createDirectories($storageOptions);

        $parentPath = $this->getFilePath(\array_merge($storageOptions, ['fileName' => null]));
        $storageOptions['fileName'] = $this->getUniqueFileName($parentPath, $fileName);

        $filePath = $this->getFilePath($storageOptions);

        try {
            $this->filesystem->writeStream(
                $filePath,
                \fopen($tempPath, 'r'),
                ['visibility' => AdapterInterface::VISIBILITY_PUBLIC]
            );
        } catch (FileExistsException $exception) {
            throw new FilenameAlreadyExistsException($filePath);
        }

        return $storageOptions;
    }

    public function load(array $storageOptions)
    {
        $filePath = $this->getFilePath($storageOptions);

        try {
            return $this->filesystem->readStream($filePath);
        } catch (FileNotFoundException $exception) {
            throw new IOException(\sprintf('Failed to open file with path "%s"', $filePath), path: $filePath);
        }
    }

    public function remove(array $storageOptions): void
    {
        $filePath = $this->getFilePath($storageOptions);

        if (!$filePath) {
            return;
        }

        try {
            $this->filesystem->delete($filePath);
        } catch (FileNotFoundException $exception) {
        }
    }

    public function move(array $sourceStorageOptions, array $targetStorageOptions): array
    {
        $this->createDirectories($targetStorageOptions);

        $targetParentPath = $this->getFilePath(\array_merge($targetStorageOptions, ['fileName' => null]));
        $targetStorageOptions['fileName'] = $this->getUniqueFileName($targetParentPath, $targetStorageOptions['fileName']);

        $targetFilePath = $this->getFilePath($targetStorageOptions);
        if ($this->filesystem->has($targetFilePath)) {
            throw new FilenameAlreadyExistsException($targetFilePath);
        }

        $this->filesystem->rename($this->getFilePath($sourceStorageOptions), $targetFilePath);

        return $targetStorageOptions;
    }

    protected function getUniqueFileName(string $parentPath, string $fileName, int $counter = 0): string
    {
        $newFileName = $fileName;
        if ($counter > 0) {
            $fileNameParts = \explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter;
            if (isset($fileNameParts[1])) {
                $newFileName .= '.' . $fileNameParts[1];
            }
        }

        $filePath = \rtrim($parentPath, '/') . '/' . \ltrim($newFileName, '/');

        if (!$this->filesystem->has($filePath)) {
            return $newFileName;
        }

        return $this->getUniqueFileName($parentPath, $fileName, $counter + 1);
    }

    /**
     * @param array<string, string|null> $storageOptions
     */
    protected function getStorageOption(array $storageOptions, string $key): ?string
    {
        return $storageOptions[$key] ?? null;
    }

    /**
     * @param array<string, string|null> $storageOptions
     */
    protected function getFilePath(array $storageOptions): string
    {
        $directory = $this->getStorageOption($storageOptions, 'directory');
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        return \implode('/', \array_filter([$directory, $segment, $fileName]));
    }

    /**
     * @param array<string, string|null> $storageOptions
     */
    private function createDirectories(array $storageOptions): void
    {
        $directory = $this->getStorageOption($storageOptions, 'directory');
        $directoryPath = \implode('/', \array_filter([$directory]));

        if ($directoryPath && !$this->filesystem->has($directoryPath)) {
            $this->filesystem->createDir($directoryPath);
        }

        $segment = $this->getStorageOption($storageOptions, 'segment');
        $segmentPath = \implode('/', \array_filter([$directory, $segment]));

        if ($segmentPath && !$this->filesystem->has($segmentPath)) {
            $this->filesystem->createDir($segmentPath);
        }
    }
}
