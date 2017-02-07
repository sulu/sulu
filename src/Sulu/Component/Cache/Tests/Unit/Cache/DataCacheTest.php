<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit;

use Sulu\Component\Cache\CacheInterface;
use Sulu\Component\Cache\DataCache;
use Symfony\Component\Filesystem\Filesystem;

class DataCacheTest extends \PHPUnit_Framework_TestCase
{
    public function provideIsFreshData()
    {
        $tmpFile = tempnam('/tmp', 'sulu-test');

        return [
            [$tmpFile, false, false],
            [$tmpFile, true, true],
        ];
    }

    /**
     * @dataProvider provideIsFreshData
     */
    public function testIsFresh($file, $createFile, $expected)
    {
        $filesystem = new Filesystem();
        if ($createFile) {
            $filesystem->touch($file);
        } elseif ($filesystem->exists($file)) {
            $filesystem->remove($file);
        }

        $cache = new DataCache($file);

        $this->assertEquals($expected, $cache->isFresh());
    }

    public function testWrite()
    {
        $file = tempnam('/tmp', 'sulu-test');
        $cache = new DataCache($file);

        $cache->write(['test' => 'test']);

        $this->assertEquals(serialize(['test' => 'test']), file_get_contents($file));

        return $cache;
    }

    /**
     * @depends testWrite
     */
    public function testRead(CacheInterface $cache)
    {
        $this->assertEquals(['test' => 'test'], $cache->read());
    }
}
