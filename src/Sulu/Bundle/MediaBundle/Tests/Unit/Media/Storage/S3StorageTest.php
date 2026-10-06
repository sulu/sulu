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

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\S3Storage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

class S3StorageTest extends TestCase
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

        new S3Storage($flysystem->reveal(), 1);
    }

    public function testGetPathWithoutPublicUrl(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('1/test.jpg')->willReturn('xxx/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://aws.com/test/xxx/1/test.jpg', $path);
    }

    public function testGetPathWithPublicUrl(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('1/test.jpg')->willReturn('xxx/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1, 'https://example.org/some');

        $path = $storage->getPath(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('https://example.org/some/xxx/1/test.jpg', $path);
    }

    public function testGetPathWithDirectory(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');
        $adapter->applyPathPrefix('trash/1/test.jpg')->willReturn('xxx/trash/1/test.jpg');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $path = $storage->getPath(['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals('http://aws.com/test/xxx/trash/1/test.jpg', $path);
    }

    public function testGetType(): void
    {
        $adapter = $this->prophesize(AwsS3Adapter::class);
        $flysystem = $this->prophesize(Filesystem::class);

        $flysystem->getAdapter()->willReturn($adapter->reveal());

        $client = $this->prophesize(S3Client::class);
        $client->getEndpoint()->willReturn('http://aws.com');
        $adapter->getClient()->willReturn($client->reveal());
        $adapter->getBucket()->willReturn('test');

        $storage = new S3Storage($flysystem->reveal(), 1);

        $type = $storage->getType(['segment' => '1', 'fileName' => 'test.jpg']);
        $this->assertEquals(StorageInterface::TYPE_REMOTE, $type);
    }
}
