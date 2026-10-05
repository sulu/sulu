<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\Storage;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\Storage\AzureBlobStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\GoogleCloudStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\S3Storage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

/**
 * The remote storages need no adapter specific code with flysystem 3.x, they ask the filesystem for the public url.
 */
class FlysystemV3StorageTest extends TestCase
{
    protected function setUp(): void
    {
        if (!FlysystemVersion::isV3()) {
            $this->markTestSkipped('Requires league/flysystem 3.x.');
        }
    }

    public function testS3StoragePathFromFilesystem(): void
    {
        $storage = new S3Storage($this->createFilesystem('https://bucket.s3.example.com'), 1);

        $this->assertSame(
            'https://bucket.s3.example.com/1/test.jpg',
            $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg'])
        );
    }

    public function testS3StoragePathWithDirectoryFromFilesystem(): void
    {
        $storage = new S3Storage($this->createFilesystem('https://bucket.s3.example.com'), 1);

        $this->assertSame(
            'https://bucket.s3.example.com/trash/1/test.jpg',
            $storage->getPath(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'])
        );
    }

    public function testS3StoragePathWithPublicUrl(): void
    {
        $storage = new S3Storage($this->createFilesystem(), 1, 'https://cdn.example.com');

        $this->assertSame(
            'https://cdn.example.com/1/test.jpg',
            $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg'])
        );
    }

    public function testS3StoragePathWithPublicUrlAndPathPrefix(): void
    {
        $storage = new S3Storage($this->createFilesystem(), 1, 'https://cdn.example.com/', '/media/');

        $this->assertSame(
            'https://cdn.example.com/media/1/test.jpg',
            $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg'])
        );
    }

    public function testS3StorageType(): void
    {
        $storage = new S3Storage($this->createFilesystem(), 1);

        $this->assertSame(StorageInterface::TYPE_REMOTE, $storage->getType([]));
    }

    public function testGoogleCloudStoragePath(): void
    {
        $storage = new GoogleCloudStorage($this->createFilesystem('https://storage.googleapis.com/bucket'), 1);

        $this->assertSame(
            'https://storage.googleapis.com/bucket/1/test.jpg',
            $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg'])
        );
        $this->assertSame(StorageInterface::TYPE_REMOTE, $storage->getType([]));
    }

    public function testAzureBlobStoragePath(): void
    {
        $storage = new AzureBlobStorage(
            $this->createFilesystem('https://account.blob.core.windows.net/container'),
            $this->createStub(BlobRestProxy::class),
            'container',
            1
        );

        $this->assertSame(
            'https://account.blob.core.windows.net/container/1/test.jpg',
            $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg'])
        );
        $this->assertSame(StorageInterface::TYPE_REMOTE, $storage->getType([]));
    }

    public function testSaveAndLoad(): void
    {
        $filesystem = $this->createFilesystem('https://bucket.s3.example.com');
        $storage = new S3Storage($filesystem, 1);

        $path = (string) \tempnam(\sys_get_temp_dir(), 'test');
        \file_put_contents($path, 'content');

        $storageOptions = $storage->save($path, 'test.jpg');

        $this->assertSame(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
        $this->assertSame('content', \stream_get_contents($storage->load($storageOptions)));
    }

    public function testSavePropagatesWriteFailure(): void
    {
        $this->expectException(UnableToWriteFile::class);

        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('writeStream')->willThrowException(UnableToWriteFile::atLocation('1/test.jpg'));

        (new S3Storage($filesystem, 1))->save(__FILE__, 'test.jpg');
    }

    public function testRemovePropagatesDeleteFailure(): void
    {
        $this->expectException(UnableToDeleteFile::class);

        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('delete')->willThrowException(UnableToDeleteFile::atLocation('1/test.jpg'));

        (new S3Storage($filesystem, 1))->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    private function createFilesystem(?string $publicUrl = null): Filesystem
    {
        return new Filesystem(new InMemoryFilesystemAdapter(), null === $publicUrl ? [] : ['public_url' => $publicUrl]);
    }
}
