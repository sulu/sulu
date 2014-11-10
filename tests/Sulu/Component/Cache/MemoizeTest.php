<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;

class MemoizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MemoizeInterface
     */
    private $mem;

    /**
     * @var Cache
     */
    private $cache;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
        $this->mem = new Memoize($this->cache);
    }

    public function testMemoize()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoize(
                function () use ($a, $b, &$called) {
                    $called++;

                    return $a + $b;
                }
            );
        };

        $v1 = $closure(1, 2);
        $id12 = sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\{closure}', serialize(array(1, 2)));
        $this->assertTrue($this->cache->contains(md5($id12)));
        $this->assertEquals(3, $this->cache->fetch(md5($id12)));
        $v2 = $closure(1, 2);

        $this->assertEquals(3, $v1);
        $this->assertEquals(3, $v2);

        $this->assertEquals(1, $called);

        $v1 = $closure(2, 3);
        $id23 = sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\{closure}', serialize(array(2, 3)));
        $this->assertTrue($this->cache->contains(md5($id12)));
        $this->assertTrue($this->cache->contains(md5($id23)));
        $this->assertEquals(3, $this->cache->fetch(md5($id12)));
        $this->assertEquals(5, $this->cache->fetch(md5($id23)));
        $v2 = $closure(2, 3);
        $v3 = $closure(1, 2);

        $this->assertEquals(5, $v1);
        $this->assertEquals(5, $v2);
        $this->assertEquals(3, $v3);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeById()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                "mem({$a}, {$b})",
                function () use ($a, $b, &$called) {
                    $called++;

                    return $a + $b;
                }
            );
        };

        $v1 = $closure(1, 2);
        $id12 = 'mem(1, 2)';
        $this->assertTrue($this->cache->contains(md5($id12)));
        $this->assertEquals(3, $this->cache->fetch(md5($id12)));
        $v2 = $closure(1, 2);

        $this->assertEquals(3, $v1);
        $this->assertEquals(3, $v2);

        $this->assertEquals(1, $called);

        $v1 = $closure(2, 3);
        $id23 = 'mem(2, 3)';
        $this->assertTrue($this->cache->contains(md5($id12)));
        $this->assertTrue($this->cache->contains(md5($id23)));
        $this->assertEquals(3, $this->cache->fetch(md5($id12)));
        $this->assertEquals(5, $this->cache->fetch(md5($id23)));
        $v2 = $closure(2, 3);
        $v3 = $closure(1, 2);

        $this->assertEquals(5, $v1);
        $this->assertEquals(5, $v2);
        $this->assertEquals(3, $v3);

        $this->assertEquals(2, $called);
    }
}
