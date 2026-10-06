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

use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\FilesystemOperator;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobStorage extends FlysystemStorage
{
    /**
     * @var AzureBlobStorageAdapter
     */
    private $adapter;

    public function __construct(
        private FilesystemInterface|FilesystemOperator $filesystem,
        private BlobRestProxy $client,
        private string $container,
        int $segments
    ) {
        parent::__construct($filesystem, $segments);

        if (FlysystemVersion::isV3()) {
            return;
        }

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AzureBlobStorageAdapter) {
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

        $blob = $this->adapter->applyPathPrefix($filePath);

        return $this->client->getBlobUrl($this->container, $blob);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
