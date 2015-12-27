<?php
/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Tests\Unit\Media\StorageManager;

use Sulu\Bundle\MediaBundle\Media\Storage\StorageInterface;
use Sulu\Bundle\MediaBundle\Media\StorageManager\StorageManager;

class StorageManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefault()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage2 = $this->prophesize(StorageInterface::class);

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            'storage1',
            $storageManager->getDefaultName()
        );
    }

    public function testDefault2()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage2 = $this->prophesize(StorageInterface::class);

        $storageManager = new StorageManager('storage2');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            'storage2',
            $storageManager->getDefaultName()
        );
    }

    public function testGetNames()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage2 = $this->prophesize(StorageInterface::class);

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            [
                'storage1',
                'storage2',
            ],
            $storageManager->getNames()
        );
    }

    public function testLoad()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->load('{"filename":"file.txt"}')->willReturn('/path/to/file.txt');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');

        $this->assertEquals('/path/to/file.txt', $storageManager->load('{"filename":"file.txt"}'));
    }

    public function testLoad2()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->load('{"filename":"file.txt"}')->willReturn('/path/to/file.txt');

        $storage2 = $this->prophesize(StorageInterface::class);
        $storage2->load('{"filename":"file.txt"}')->willReturn('/path/to/file2.txt');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals('/path/to/file2.txt', $storageManager->load('{"filename":"file.txt"}', 'storage2'));
    }

    public function testSave()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->save('/path/to/file.txt', 'file.txt', '{"filename":"file.txt"}')
            ->willReturn('{"filename":"file2.txt"}');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');

        $this->assertEquals(
            '{"filename":"file2.txt"}',
            $storageManager->save('/path/to/file.txt', 'file.txt', '{"filename":"file.txt"}')
        );
    }

    public function testSave2()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->save('/path/to/file.txt', 'file.txt', '{"filename":"file.txt"}')
            ->willReturn('{"filename":"file2.txt"}');

        $storage2 = $this->prophesize(StorageInterface::class);
        $storage2->save('/path/to/file.txt', 'file.txt', '{"filename":"file.txt"}')
            ->willReturn('{"filename":"file4.txt"}');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            '{"filename":"file4.txt"}',
            $storageManager->save('/path/to/file.txt', 'file.txt', '{"filename":"file.txt"}', 'storage2')
        );
    }

    public function testGetDownloadUrl()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->getDownloadUrl('{"filename":"file.txt"}')->willReturn('http://www.test.com/file.txt');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');

        $this->assertEquals(
            'http://www.test.com/file.txt',
            $storageManager->getDownloadUrl('{"filename":"file.txt"}')
        );
    }

    public function testGetDownloadUrl2()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->getDownloadUrl('{"filename":"file.txt"}')->willReturn('http://www.test.com/file.txt');

        $storage2 = $this->prophesize(StorageInterface::class);
        $storage2->getDownloadUrl('{"filename":"file.txt"}')->willReturn('http://www.test.com/file2.txt');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            'http://www.test.com/file2.txt',
            $storageManager->getDownloadUrl('{"filename":"file.txt"}', 'storage2')
        );
    }

    public function testRemove()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->remove('{"filename":"file.txt"}')->willReturn('done');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');

        $this->assertEquals(
            'done',
            $storageManager->remove('{"filename":"file.txt"}')
        );
    }

    public function testRemove2()
    {
        $storage1 = $this->prophesize(StorageInterface::class);
        $storage1->remove('{"filename":"file.txt"}')->willReturn('done');

        $storage2 = $this->prophesize(StorageInterface::class);
        $storage2->remove('{"filename":"file.txt"}')->willReturn('done2');

        $storageManager = new StorageManager('storage1');
        $storageManager->add($storage1->reveal(), 'storage1');
        $storageManager->add($storage2->reveal(), 'storage2');

        $this->assertEquals(
            'done2',
            $storageManager->remove('{"filename":"file.txt"}', 'storage2')
        );
    }
}
