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
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;

abstract class FlysystemStorage implements StorageInterface
{
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var int
     */
    private $segments;

    public function __construct(FilesystemInterface $filesystem, int $segments)
    {
        $this->filesystem = $filesystem;
        $this->segments = $segments;
    }

    public function save(string $tempPath, string $fileName, array $storageOptions = []): array
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        if (!$segment) {
            $segment = sprintf('%0' . strlen($this->segments) . 'd', rand(1, $this->segments));
        }

        $fileName = $this->getUniqueFileName($segment, $fileName);
        $filePath = $segment . '/' . $fileName;

        if (!$this->filesystem->has($segment)) {
            $this->filesystem->createDir($segment);
        }

        try {
            $this->filesystem->writeStream(
                $filePath,
                fopen($tempPath, 'r'),
                ['visibility' => AdapterInterface::VISIBILITY_PUBLIC]
            );
        } catch (FileExistsException $exception) {
            throw new FilenameAlreadyExistsException($filePath);
        }

        return [
            'segment' => $segment,
            'fileName' => $fileName,
        ];
    }

    public function load(array $storageOptions)
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');
        $path = $segment . '/' . $fileName;

        try {
            return $this->filesystem->readStream($path);
        } catch (FileNotFoundException $exception) {
            throw new ImageProxyMediaNotFoundException(sprintf('Original media at path "%s" not found', $path));
        }
    }

    public function remove(array $storageOptions): void
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        if (!$segment || !$fileName) {
            return;
        }

        try {
            $this->filesystem->delete($segment . '/' . $fileName);
        } catch (FileNotFoundException $exception) {
        }
    }

    protected function getUniqueFileName(string $folder, string $fileName, int $counter = 0): string
    {
        $newFileName = $fileName;
        if ($counter > 0) {
            $fileNameParts = explode('.', $fileName, 2);
            $newFileName = $fileNameParts[0] . '-' . $counter;
            if (isset($fileNameParts[1])) {
                $newFileName .= '.' . $fileNameParts[1];
            }
        }

        if (!$this->filesystem->has($folder . '/' . $newFileName)) {
            return $newFileName;
        }

        return $this->getUniqueFileName($folder, $fileName, $counter + 1);
    }

    protected function getStorageOption(array $storageOptions, string $key): ?string
    {
        return $storageOptions[$key] ?? null;
    }
}
