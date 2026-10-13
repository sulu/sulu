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
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Sulu\Bundle\MediaBundle\Media\Exception\FilenameAlreadyExistsException;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemV3Storage;
use Symfony\Component\Filesystem\Exception\IOException;

class FlysystemV3StorageTest extends TestCase
{
    use ProphecyTrait;

    private FlysystemV3Storage $flysystemStorage;

    private FilesystemOperator $flysystem;

    protected function setUp(): void
    {
        if (!\interface_exists(FilesystemOperator::class)) {
            $this->markTestSkipped('Requires league/flysystem 3.x.');
        }

        $adapter = new InMemoryFilesystemAdapter();
        $this->flysystem = new Filesystem($adapter);

        $this->flysystemStorage = new FlysystemV3Storage(
            $this->flysystem,
            $adapter,
            100,
            null,
        );
    }

    public function testSave(): void
    {
        $storageOptions = [
            'segment' => '01',
        ];

        self::assertEquals(
            [
                'fileName' => 'image.jpg',
                'segment' => '01',
            ],
            $this->flysystemStorage->save(__FILE__, 'image.jpg', $storageOptions)
        );

        self::assertTrue($this->flysystem->has('01/image.jpg'));
    }

    public function testSaveWithRandomSegment(): void
    {
        $storageOptions = [];

        $newStorageOptions = $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions);

        self::assertEquals($newStorageOptions['fileName'] ?? '', 'code.php');
        self::assertEquals(3, \strlen((string) ($newStorageOptions['segment'] ?? '')));

        self::assertTrue($this->flysystem->has(($newStorageOptions['segment'] ?? '') . '/code.php'));
    }

    public function testSaveUnableToReadSource(): void
    {
        $storageOptions = [
            'sement' => '01',
        ];

        $this->expectException(FilenameAlreadyExistsException::class);

        $this->flysystemStorage->save(__DIR__ . '/does_not_exist', 'image.jpg', $storageOptions);
    }

    public function testSaveIntoDirectory(): void
    {
        $storageOptions = [
            'directory' => '/tmp/flysystem',
            'segment' => '01',
        ];

        self::assertEquals(
            \array_merge($storageOptions, [
                'fileName' => 'code.php',
            ]),
            $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions),
        );

        self::assertTrue($this->flysystem->has('tmp/flysystem/01/code.php'));
    }

    public function testSaveNonUniqueFileName(): void
    {
        $storageOptions = [
            'segment' => '08',
        ];

        $this->flysystem->write('08/code.php', 'Some code');

        $newStorageOptions = $this->flysystemStorage->save(__FILE__, 'code.php', $storageOptions);
        self::assertEquals(
            [
                'fileName' => 'code-1.php',
                'segment' => '08',
            ],
            $newStorageOptions
        );
        self::assertTrue($this->flysystem->has('08/code-1.php'));
    }

    public function testLoad(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'code.php',
        ];

        $this->flysystem->write('08/code.php', 'Some code');

        $loadedResource = $this->flysystemStorage->load($storageOptions);

        self::assertEquals('Some code', \stream_get_contents($loadedResource));
    }

    public function testRemove(): void
    {
        $storageOptions = [
            'segment' => '08',
            'fileName' => 'plant.jpg',
        ];

        $this->flysystem->write('08/plant.jpg', 'Flowers and stuff');

        $this->flysystemStorage->remove($storageOptions);

        self::assertFalse($this->flysystem->has('08/plant.jpg'));
    }

    public function testMove(): void
    {
        $sourceStorageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];
        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $this->flysystem->write('08/test.jpg', 'Some file');
        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $this->assertEquals($targetStorageOptions, $outputStorageOptions);

        self::assertTrue($this->flysystem->has('10/hallo.jpg'));
        self::assertFalse($this->flysystem->has('08/test.jpg'));
    }

    public function testMoveToExistingFile(): void
    {
        $sourceStorageOptions = [
            'segment' => '08',
            'fileName' => 'test.jpg',
        ];
        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $this->flysystem->write('08/test.jpg', 'Some file');
        $this->flysystem->write('10/hallo.jpg', 'Already there');
        $outputStorageOptions = $this->flysystemStorage->move($sourceStorageOptions, $targetStorageOptions);

        $targetStorageOptions = [
            'segment' => '10',
            'fileName' => 'hallo-1.jpg',
        ];
        $this->assertEquals($targetStorageOptions, $outputStorageOptions);

        self::assertTrue($this->flysystem->has('10/hallo.jpg'));
        self::assertTrue($this->flysystem->has('10/hallo-1.jpg'));
        self::assertFalse($this->flysystem->has('08/test.jpg'));
    }

    public function testGetPath(): void
    {
        $storageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $filePath = $this->flysystemStorage->getPath($storageOptions);

        $this->assertEquals('10/hallo.jpg', $filePath);
    }

    public function testGetType(): void
    {
        $storageOptions = [
            'segment' => '10',
            'fileName' => 'hallo.jpg',
        ];

        $filePath = $this->flysystemStorage->getType($storageOptions);

        $this->assertEquals('local', $filePath);
    }

    public function testGetPathWithRootPath(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $storage = new FlysystemV3Storage(new Filesystem($adapter), $adapter, 10, '/var/uploads');

        $this->assertSame(
            '/var/uploads/10/hallo.jpg',
            $storage->getPath(['segment' => '10', 'fileName' => 'hallo.jpg'])
        );
    }

    public function testGetPathAndTypeOfRemoteAdapter(): void
    {
        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->publicUrl('10/hallo.jpg')->willReturn('https://cdn.example.com/10/hallo.jpg');
        $storage = new FlysystemV3Storage($filesystem->reveal(), $this->prophesize(FilesystemAdapter::class)->reveal(), 10, null);

        $storageOptions = ['segment' => '10', 'fileName' => 'hallo.jpg'];

        $this->assertSame('remote', $storage->getType($storageOptions));
        $this->assertSame('https://cdn.example.com/10/hallo.jpg', $storage->getPath($storageOptions));
    }

    public function testSaveSetsPublicVisibility(): void
    {
        $filesystem = $this->prophesize(FilesystemOperator::class);
        $filesystem->has(Argument::any())->willReturn(false);
        $filesystem->createDirectory('01')->shouldBeCalled();
        $filesystem->writeStream('01/image.jpg', Argument::any(), ['visibility' => 'public'])->shouldBeCalled();
        $storage = new FlysystemV3Storage($filesystem->reveal(), $this->prophesize(FilesystemAdapter::class)->reveal(), 10, null);

        $storage->save(__FILE__, 'image.jpg', ['segment' => '01']);
    }

    public function testRemoveOfMissingFile(): void
    {
        $this->flysystemStorage->remove(['segment' => '08', 'fileName' => 'missing.jpg']);

        $this->expectNotToPerformAssertions();
    }

    public function testLoadOfMissingFile(): void
    {
        $this->expectException(IOException::class);

        $this->flysystemStorage->load(['segment' => '08', 'fileName' => 'missing.jpg']);
    }
}
