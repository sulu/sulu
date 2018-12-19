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

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class GoogleCloudStorage extends FlysystemStorage
{
    /**
     * @var GoogleStorageAdapter
     */
    private $adapter;

    public function __construct(FilesystemInterface $filesystem, int $segments)
    {
        parent::__construct($filesystem, $segments);

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof GoogleStorageAdapter) {
            throw new \RuntimeException();
        }

        $this->adapter = $filesystem->getAdapter();
    }

    public function getPath(array $storageOptions): string
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        return $this->adapter->getUrl($segment . '/' . $fileName);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
