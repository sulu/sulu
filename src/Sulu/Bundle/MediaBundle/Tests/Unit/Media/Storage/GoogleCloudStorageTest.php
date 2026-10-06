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
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\GoogleCloudStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class GoogleCloudStorageTest extends TestCase
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

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        new GoogleCloudStorage($flysystem->reveal(), 1);
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

    public function testGetPathWithDirectory(): void
    {
        $adapter = $this->prophesize(GoogleStorageAdapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $storage = new GoogleCloudStorage($flysystem->reveal(), 1);

        $adapter->getUrl('trash/1/test.jpg')->willReturn('http://google.com/trash/1/test.jpg')->shouldBeCalled();

        $path = $storage->getPath(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://google.com/trash/1/test.jpg', $path);
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
