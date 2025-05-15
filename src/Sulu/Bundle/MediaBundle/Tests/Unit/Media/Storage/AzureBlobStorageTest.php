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

use League\Flysystem\AdapterInterface;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Exception\IOException;

class AzureBlobStorageTest extends TestCase
{
    use ProphecyTrait;

    public function testConstruct(): void
    {
        $this->expectException(\RuntimeException::class);

        $adapter = $this->prophesize(AdapterInterface::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);
    }

    public function testSave(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveDirectoryExists(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('1/test.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(true);
        $flysystem->writeStream('1/test.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir(Argument::any())->shouldNotBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test.jpg'], $storageOptions);
    }

    public function testSaveUniqueFileName(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('1/test.jpg')->wilLReturn(true);
        $flysystem->has('1/test-1.jpg')->wilLReturn(true);
        $flysystem->has('1/test-2.jpg')->wilLReturn(false);
        $flysystem->has('1')->wilLReturn(false);
        $flysystem->writeStream('1/test-2.jpg', Argument::any(), ['visibility' => AdapterInterface::VISIBILITY_PUBLIC])
            ->shouldBeCalled();

        $flysystem->createDir('1')->shouldBeCalled();

        $storageOptions = $storage->save(\tempnam(\sys_get_temp_dir(), 'test'), 'test.jpg');
        $this->assertEquals(['segment' => '1', 'fileName' => 'test-2.jpg'], $storageOptions);
    }

    public function testLoad(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $handle = \tmpfile();
        $flysystem->readStream('1/test.jpg')->willReturn($handle)->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(\stream_get_contents($handle), \stream_get_contents($result));
    }

    public function testLoadWithDirectory(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        /** @var resource $handle */
        $handle = \tmpfile();
        $flysystem->readStream('trash/1/test.jpg')->willReturn($handle)->shouldBeCalled();

        $result = $storage->load(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(\stream_get_contents($handle), \stream_get_contents($result));
    }

    public function testLoadNotFound(): void
    {
        $this->expectException(IOException::class);

        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $handle = \tmpfile();
        $flysystem->readStream('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $result = $storage->load(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals($handle, $result);
    }

    public function testRemove(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->delete('1/test.jpg')->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemoveWithDirectory(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->delete('trash/1/test.jpg')->shouldBeCalled();

        $storage->remove(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testRemoveNotFound(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->delete('1/test.jpg')->willThrow(new FileNotFoundException('1/test.jpg'))->shouldBeCalled();

        $storage->remove(['segment' => '1', 'fileName' => 'test.jpg']);
    }

    public function testMove(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('trash')->wilLReturn(false);
        $flysystem->createDir('trash')->shouldBeCalled();

        $flysystem->has('trash/1')->wilLReturn(false);
        $flysystem->createDir('trash/1')->shouldBeCalled();

        $flysystem->has('trash/1/test.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $result);
    }

    public function testMoveTargetDirectoryExists(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('trash')->wilLReturn(true);
        $flysystem->has('trash/1')->wilLReturn(true);

        $flysystem->has('trash/1/test.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'], $result);
    }

    public function testMoveTargetFileExists(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $flysystem->has('trash')->wilLReturn(true);
        $flysystem->has('trash/1')->wilLReturn(true);

        $flysystem->has('trash/1/test.jpg')->wilLReturn(true);
        $flysystem->has('trash/1/test-1.jpg')->wilLReturn(true);
        $flysystem->has('trash/1/test-2.jpg')->wilLReturn(false);
        $flysystem->rename('1/test.jpg', 'trash/1/test-2.jpg')->shouldBeCalled();

        $result = $storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']
        );

        $this->assertSame(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test-2.jpg'], $result);
    }

    public function testGetPath(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $adapter->applyPathPrefix('1/test.jpg')->willReturn('1/test.jpg')->shouldBeCalled();

        $client
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('test-container', '1/test.jpg')
            ->willReturn('http://azure.com/test-container/1/test.jpg')
        ;

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://azure.com/test-container/1/test.jpg', $path);
    }

    public function testGetPathWithDirectory(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $adapter->applyPathPrefix('trash/1/test.jpg')->willReturn('trash/1/test.jpg')->shouldBeCalled();

        $client
            ->expects($this->once())
            ->method('getBlobUrl')
            ->with('test-container', 'trash/1/test.jpg')
            ->willReturn('http://azure.com/test-container/trash/1/test.jpg')
        ;

        $path = $storage->getPath(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://azure.com/test-container/trash/1/test.jpg', $path);
    }

    public function testGetType(): void
    {
        $adapter = $this->prophesize(AzureBlobStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);

        $type = $storage->getType(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(StorageInterface::TYPE_REMOTE, $type);
    }
}
