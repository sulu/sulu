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

use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\MediaBundle\Media\Exception\ImageProxyMediaNotFoundException;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class GoogleCloudStorageTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->expectException(\RuntimeException::class);

        $adapter = $this->prophesize(AdapterInterface::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        new GoogleCloudStorage($flysystem->reveal(), 1);
    }

    public function testSave(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(tempnam(sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveDirectoryExists(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(true);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir(Argument::any())->shouldNotBeCalled();

        $storageOptions = $storage->save(tempnam(sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveUniqueFileName(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $flysystem->has('1/test.jpg')->wilLReturn(true);
        $flysystem->has('1/test-1.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test-1.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(tempnam(sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test-1.jpg'], $storageOptions);
    }

    public function testLoad(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $handle = tmpfile();
        $flysystem->readStream('1/test.jpg')->willReturn($handle)->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testLoadNotFound(): void
    {
        $this->expectException(ImageProxyMediaNotFoundException::class);

        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $handle = tmpfile();
        $flysystem->readStream('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testRemove(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $flysystem->delete('1/test.jpg')->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemoveNotFound(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $flysystem->delete('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testGetPath(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $adapter->getUrl('1/test.jpg')->willReturn('http://google.com/1/test.jpg')->shouldBeCalled();

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://google.com/1/test.jpg', $path);
    }

    public function testGetType(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $type = $storage->getType(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(StorageInterface::TYPE_REMOTE, $type);
    }
}
