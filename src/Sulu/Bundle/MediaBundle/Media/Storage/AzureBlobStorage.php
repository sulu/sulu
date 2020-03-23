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
use MicrosoftAzure\Storage\Blob\BlobRestProxy;

class AzureBlobStorage extends FlysystemStorage
{
    /**
     * @var AzureBlobStorageAdapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $container;

    /**
     * @var BlobRestProxy
     */
    private $client;

    public function __construct(
        FilesystemInterface $filesystem,
        BlobRestProxy $client,
        string $container,
        int $segments
    ) {
        parent::__construct($filesystem, $segments);

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AzureBlobStorageAdapter) {
            throw new \RuntimeException();
        }

        $this->client = $client;
        $this->container = $container;
        $this->adapter = $filesystem->getAdapter();
    }

    /**
     * Azure Filesystem returns a not seekable resource.
     *
     * @inheritDoc
     */
    public function load(array $storageOptions)
    {
        $resource = parent::load($storageOptions);
        // Azure Filesystem returns a not seekable resource
        // Need converted to a seekable resource to allow get mimetype from it in the MediaImageExtractor
        $contents = stream_get_contents($resource);

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return $stream;
    }

    public function getPath(array $storageOptions): string
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        $blob = $this->adapter->applyPathPrefix($segment . '/' . $fileName);

        return $this->client->getBlobUrl($this->container, $blob);
    }

    public function getType(array $storageOptions): string
    {
        return self::TYPE_REMOTE;
    }
}
