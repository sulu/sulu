<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Functional\Media\Storage;

use Sulu\Bundle\MediaBundle\Media\Storage\S3Storage;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Tests\Application\S3Kernel;
use Sulu\Bundle\MediaBundle\Tests\Functional\Mock\S3AdapterMock;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class S3StorageTest extends SuluTestCase
{
    protected function setUp(): void
    {
        static::$class = S3Kernel::class;
    }

    protected function tearDown(): void
    {
        static::$class = null;
        parent::tearDown();
    }

    public function testSave(): void
    {
        $kernel = self::bootKernel();

        /** @var S3AdapterMock $adapter */
        $adapter = $kernel->getContainer()->get('sulu_media.storage.s3.adapter');
        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $result = $storage->save($this->getImagePath(), 'sulu.jpg', []);

        $this->assertArrayHasKey('segment', $result);
        $this->assertSame('sulu.jpg', $result['fileName']);
        $this->assertNotNull($result['segment']);
        $this->assertTrue($adapter->has($result['segment']));
        $this->assertTrue($adapter->has($result['segment'] . '/sulu.jpg'));
    }

    public function testLoad(): void
    {
        $kernel = self::bootKernel();

        /** @var S3AdapterMock $adapter */
        $adapter = $kernel->getContainer()->get('sulu_media.storage.s3.adapter');
        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $file = \file_get_contents($this->getImagePath());

        $adapter->addDirectory('02');
        $adapter->addFile('02/sulu.jpg', $file);

        $result = $storage->load(['segment' => '02', 'fileName' => 'sulu.jpg']);

        $this->assertSame($file, \stream_get_contents($result));
    }

    public function testRemove(): void
    {
        $kernel = self::bootKernel();

        /** @var S3AdapterMock $adapter */
        $adapter = $kernel->getContainer()->get('sulu_media.storage.s3.adapter');
        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $file = \file_get_contents($this->getImagePath());

        $adapter->addDirectory('02');
        $adapter->addFile('02/sulu.jpg', $file);

        $storage->remove(['segment' => '02', 'fileName' => 'sulu.jpg']);

        $this->assertFalse($adapter->has('02/sulu.jpg'));
    }

    public function testMove(): void
    {
        $kernel = self::bootKernel();

        /** @var S3AdapterMock $adapter */
        $adapter = $kernel->getContainer()->get('sulu_media.storage.s3.adapter');
        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $sourceStorageOptions = $storage->save($this->getImagePath(), 'sulu.jpg', []);
        $sourceFilePath = $sourceStorageOptions['segment'] . '/' . $sourceStorageOptions['fileName'];

        $this->assertTrue($adapter->has($sourceFilePath));

        $targetStorageOptions = ['directory' => 'trash', 'segment' => '5', 'fileName' => 'new-name.jpg'];
        $storage->move($sourceStorageOptions, $targetStorageOptions);

        $this->assertFalse($adapter->has($sourceFilePath));
        $this->assertTrue($adapter->has('trash/5/new-name.jpg'));
    }

    public function testGetPath(): void
    {
        $kernel = self::bootKernel();

        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $result = $storage->getPath(['segment' => '02', 'fileName' => 'sulu.jpg']);

        $this->assertStringContainsString('eu-west-1', $result);
        $this->assertStringContainsString('test-bucket', $result);
        $this->assertStringContainsString('02/sulu.jpg', $result);
    }

    public function testGetType(): void
    {
        $kernel = self::bootKernel();

        $storage = $kernel->getContainer()->get('sulu_media.storage.s3');
        $this->assertInstanceOf(S3Storage::class, $storage);

        $result = $storage->getType(['segment' => '02', 'fileName' => 'sulu.jpg']);

        $this->assertSame(StorageInterface::TYPE_REMOTE, $result);
    }

    /**
     * @return string
     */
    private function getImagePath()
    {
        return __DIR__ . '/../../../Fixtures/files/photo.jpeg';
    }
}
