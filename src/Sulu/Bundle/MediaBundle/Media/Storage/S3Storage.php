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

use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class S3Storage extends FlysystemStorage
{
    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $bucketName;

    public function __construct(FilesystemInterface $filesystem, int $segments)
    {
        parent::__construct($filesystem, $segments);

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AwsS3Adapter) {
            throw new \RuntimeException('This storage can only handle filesystems with "AwsS3Adapter".');
        }

        /** @var AwsS3Adapter $adapter */
        $adapter = $filesystem->getAdapter();

        $this->endpoint = (string) $adapter->getClient()->getEndpoint();
        $this->bucketName = $adapter->getBucket();
    }

    public function getPath(array $storageOptions): string
    {
        $segment = $this->getStorageOption($storageOptions, 'segment');
        $fileName = $this->getStorageOption($storageOptions, 'fileName');

        return $this->endpoint . '/' . $this->bucketName . '/' . $segment . '/' . $fileName;
    }

    public function getType(array $storageOptions): string
    {
        return StorageInterface::TYPE_REMOTE;
    }
}
