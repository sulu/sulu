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

    public function __construct(FilesystemInterface $filesystem, int $segments)
    {
        parent::__construct($filesystem, $segments);

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AzureBlobStorageAdapter) {
            throw new \RuntimeException();
        }

        $this->adapter = $filesystem->getAdapter();
    }

    public function setContainer(string $container)
    {
        $this->container = $container;
    }

    public function setClient(BlobRestProxy $client)
    {
        $this->client = $client;
    }

    public function load(array $storageOptions)
    {
        $resource = parent::load($storageOptions);
        $contents = stream_get_contents($resource);

        $stream = fopen('php://memory','r+');
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
