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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemStorage;
use Sulu\Bundle\MediaBundle\Media\Storage\FlysystemVersion;
use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;

/**
 * The in-memory adapters hide visibility, directories and the name of the move method, so these calls are checked on a
 * mock. Runs with flysystem 1.x and 3.x.
 */
class FlysystemStorageOperationsTest extends TestCase
{
    /**
     * @var MockObject&Filesystem
     */
    private $filesystem;

    private FlysystemStorage $storage;

    /**
     * @var non-empty-string
     */
    private string $createDirectoryMethod;

    /**
     * @var non-empty-string
     */
    private string $moveMethod;

    protected function setUp(): void
    {
        $this->createDirectoryMethod = FlysystemVersion::isV3() ? 'createDirectory' : 'createDir';
        $this->moveMethod = FlysystemVersion::isV3() ? 'move' : 'rename';

        $this->filesystem = $this->createMock(Filesystem::class);
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

    public function testSaveWritesPublicFile(): void
    {
        $this->filesystem->expects($this->once())
            ->method('writeStream')
            ->with('1/test.jpg', $this->anything(), ['visibility' => 'public']);

        $this->storage->save(__FILE__, 'test.jpg');
    }

    public function testSaveCreatesDirectoryAndSegment(): void
    {
        $createdDirectories = [];
        $this->filesystem->expects($this->exactly(2))
            ->method($this->createDirectoryMethod)
            ->willReturnCallback(function(string $path) use (&$createdDirectories) {
                $createdDirectories[] = $path;

                return true;
            });

        $this->storage->save(__FILE__, 'test.jpg', ['directory' => 'trash']);

        $this->assertSame(['trash', 'trash/1'], $createdDirectories);
    }

    public function testSaveDoesNotCreateExistingDirectories(): void
    {
        $this->filesystem->method('has')->willReturnCallback(
            fn (string $path) => \in_array($path, ['trash', 'trash/1'], true)
        );
        $this->filesystem->expects($this->never())->method($this->createDirectoryMethod);

        $this->storage->save(__FILE__, 'test.jpg', ['directory' => 'trash']);
    }

    public function testMoveUsesMoveMethodOfFlysystem(): void
    {
        $this->filesystem->expects($this->once())
            ->method($this->moveMethod)
            ->with('1/test.jpg', 'trash/1/test.jpg');

        $this->storage->move(
            ['segment' => '1', 'fileName' => 'test.jpg'],
            ['directory' => 'trash', 'segment' => '1', 'fileName' => 'test.jpg'],
        );
    }
}
