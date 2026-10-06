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
use League\Flysystem\FilesystemOperator;

class S3Storage extends FlysystemStorage
{
    /**
     * @var AwsS3Adapter
     */
    private $adapter;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var string
     */
    private $bucketName;

    /**
     * @param string|null $pathPrefix Prefix of the adapter, only used with flysystem 3.x to build the url of the $publicUrl
     */
    public function __construct(
        private FilesystemInterface|FilesystemOperator $filesystem,
        int $segments,
        private ?string $publicUrl = null,
        private ?string $pathPrefix = null,
    ) {
        parent::__construct($filesystem, $segments);

        if (FlysystemVersion::isV3()) {
            return;
        }

        if (!$filesystem instanceof Filesystem || !$filesystem->getAdapter() instanceof AwsS3Adapter) {
            throw new \RuntimeException('This storage can only handle filesystems with "AwsS3Adapter".');
        }

        $this->adapter = $filesystem->getAdapter();

        $this->endpoint = (string) $this->adapter->getClient()->getEndpoint();
        $this->bucketName = $this->adapter->getBucket();

        $this->publicUrl ??= $this->endpoint . '/' . $this->bucketName;
    }

    public function getPath(array $storageOptions): string
    {
        $filePath = $this->getFilePath($storageOptions);

        if (FlysystemVersion::isV3()) {
            return $this->getPathV3($filePath);
        }

        $path = $this->adapter->applyPathPrefix($filePath);

        return $this->publicUrl . '/' . \ltrim($path, '/');
    }

    public function getType(array $storageOptions): string
    {
        return StorageInterface::TYPE_REMOTE;
    }

    private function getPathV3(string $filePath): string
    {
        if (null === $this->publicUrl) {
            return $this->filesystem->publicUrl($filePath);
        }

        $prefix = \trim((string) $this->pathPrefix, '/');
        $path = '' === $prefix ? $filePath : $prefix . '/' . $filePath;

        return \rtrim($this->publicUrl, '/') . '/' . \ltrim($path, '/');
    }
}
