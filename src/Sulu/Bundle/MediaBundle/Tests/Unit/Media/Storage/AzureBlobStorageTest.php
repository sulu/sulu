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

use League\Flysystem\AdapterInterface;
use League\Flysystem\AzureBlobStorage\AzureBlobStorageAdapter;
use League\Flysystem\Filesystem;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Storage\AzureBlobStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class AzureBlobStorageTest extends TestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        if (FlysystemVersion::isV3()) {
            $this->markTestSkipped('Requires league/flysystem 1.x.');
        }
    }

    public function testConstruct(): void
    {
        $this->expectException(\RuntimeException::class);

        $adapter = $this->prophesize(AdapterInterface::class);
        $flysystem = $this->prophesize(Filesystem::class);
        $client = $this->createMock(BlobRestProxy::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        new AzureBlobStorage($flysystem->reveal(), $client, 'test-container', 1);
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
