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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class GoogleCloudStorage extends FlysystemStorage
{
    private FilesystemInterface|FilesystemOperator $filesystem;

    /**
     * @var GoogleStorageAdapter
     */
    private $adapter;

    public function __construct(FilesystemInterface|FilesystemOperator $filesystem, int $segments)
    {
        parent::__construct($filesystem, $segments);

        $this->filesystem = $filesystem;

        if (FlysystemVersion::isV3()) {
            return;
        }

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof GoogleStorageAdapter) {
            throw new \RuntimeException();
        }

        $this->adapter = $filesystem->getAdapter();
    }

    public function getPath(array $storageOptions): string
    {
        $filePath = $this->getFilePath($storageOptions);

        if (FlysystemVersion::isV3()) {
            return $this->filesystem->publicUrl($filePath);
        }

        return $this->adapter->getUrl($filePath);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
