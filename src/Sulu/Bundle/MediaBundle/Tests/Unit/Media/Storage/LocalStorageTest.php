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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class LocalStorageTest extends TestCase
{
    private LocalStorage $localStorage;

    protected function setUp(): void
    {
        $fileSystem = new Filesystem();
        $this->localStorage = new LocalStorage(__DIR__ . '/../../../uploads/media', '10', $fileSystem);
    }

    public function testLoadReturnsFileHandle(): void
    {
        $storageOptions = [
            'fileName' => '023a9fe8-d5f3-4bdd-a6a3-13a935154105.jpg',
            'segment' => '01',
        ];
        $pathName = $this->localStorage->getPath($storageOptions);
        \mkdir(\dirname($pathName), 0777, true);
        \file_put_contents($pathName, 'test');

        $fp = $this->localStorage->load($storageOptions);

        self::assertTrue(\is_resource($fp));

        // Cleanup file and dir
        \unlink($pathName);
        \rmdir(\dirname($pathName));
    }

    public function testSaveGivesTheFileTheDefaultPermissionsOfANewFile(): void
    {
        if ('/' !== \DIRECTORY_SEPARATOR) {
            self::markTestSkipped('File permissions are not supported on Windows.');
        }

        $tempPath = \tempnam(\sys_get_temp_dir(), 'sulu_media_test');
        self::assertNotFalse($tempPath);
        \file_put_contents($tempPath, 'test');
        // an uploaded file is only readable by its owner
        \chmod($tempPath, 0600);

        $storageOptions = $this->localStorage->save($tempPath, 'permissions-test.jpg', ['segment' => '02']);
        $pathName = $this->localStorage->getPath($storageOptions);

        try {
            self::assertSame(0666 & ~\umask(), \fileperms($pathName) & 0777);
        } finally {
            \unlink($pathName);
            \unlink($tempPath);
        }
    }

    public function testLoadThrowsExceptionOnNonExistingFile(): void
    {
        self::expectException(IOException::class);

        $this->localStorage->load([
            'fileName' => 'a-file-that-does-not-exist.jpg',
            'segment' => '01',
        ]);
    }
}
