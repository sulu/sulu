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
use League\Flysystem\Memory\MemoryAdapter;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Runs with flysystem 1.x (league/flysystem-memory ^1.0) and with flysystem 3.x (league/flysystem-memory ^3.0).
 */
class FlysystemStorageTest extends TestCase
{
    private Filesystem $filesystem;

    private FlysystemStorage $storage;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem(FlysystemVersion::isV3() ? new InMemoryFilesystemAdapter() : new MemoryAdapter());
        $this->storage = new class($this->filesystem, 1) extends FlysystemStorage {
            public function getPath(array $storageOptions): string
            {
                return $this->getFilePath($storageOptions);
            }

            public function getType(array $storageOptions): string
            {
                return StorageInterface::TYPE_REMOTE;
            }
        };
    }

    public function testSave(): void
    {
        $storageOptions = $this->storage->save($this->createTempFile('content'), 'test.jpg');

        $this->assertSame(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
        $this->assertSame('content', $this->filesystem->read('1/test.jpg'));
    }

    public function testSaveWithDirectory(): void
    {
        $storageOptions = $this->storage->save($this->createTempFile('content'), 'test.jpg', ['directory' => 'trash']);

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
        $this->assertSame('content', $this->filesystem->read('trash/1/test.jpg'));
    }

    public function testSaveWithExistingDirectory(): void
    {
        $this->filesystem->write('1/other.jpg', 'other');

        $storageOptions = $this->storage->save($this->createTempFile('content'), 'test.jpg');

        $this->assertSame(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
        $this->assertSame('other', $this->filesystem->read('1/other.jpg'));
    }

    public function testSaveUniqueFileName(): void
    {
        $this->filesystem->write('1/test.jpg', 'existing');

        $storageOptions = $this->storage->save($this->createTempFile('content'), 'test.jpg');

        $this->assertSame(['segment' => '1', 'fileName' => 'test-1.jpg'], $storageOptions);
        $this->assertSame('existing', $this->filesystem->read('1/test.jpg'));
        $this->assertSame('content', $this->filesystem->read('1/test-1.jpg'));
    }

    public function testLoad(): void
    {
        $this->filesystem->write('1/test.jpg', 'content');

        $stream = $this->storage->load(['segment' => '1', 'fileName' => 'test.jpg']);

        $this->assertSame('content', \stream_get_contents($stream));
    }

    public function testLoadWithDirectory(): void
    {
        $this->filesystem->write('trash/1/test.jpg', 'content');

        $stream = $this->storage->load(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);

        $this->assertSame('content', \stream_get_contents($stream));
    }

    public function testLoadNotFound(): void
    {
        $this->expectException(IOException::class);

        $this->storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemove(): void
    {
        $this->filesystem->write('1/test.jpg', 'content');

        $this->storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);

        $this->assertFalse($this->filesystem->has('1/test.jpg'));
    }

    public function testRemoveNotFound(): void
    {
        $this->storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);

        $this->assertFalse($this->filesystem->has('1/test.jpg'));
    }

    public function testMove(): void
    {
        $this->filesystem->write('1/test.jpg', 'content');

        $storageOptions = $this->storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'],
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
        $this->assertFalse($this->filesystem->has('1/test.jpg'));
        $this->assertSame('content', $this->filesystem->read('trash/1/test.jpg'));
    }

    public function testMoveUniqueFileName(): void
    {
        $this->filesystem->write('1/test.jpg', 'content');
        $this->filesystem->write('trash/1/test.jpg', 'existing');

        $storageOptions = $this->storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'],
        );

        $this->assertSame('test-1.jpg', $storageOptions['fileName']);
        $this->assertSame('existing', $this->filesystem->read('trash/1/test.jpg'));
        $this->assertSame('content', $this->filesystem->read('trash/1/test-1.jpg'));
    }

    private function createTempFile(string $content): string
    {
        $path = (string) \tempnam(\sys_get_temp_dir(), 'test');
        \file_put_contents($path, $content);

        return $path;
    }
}
