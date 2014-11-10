<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;

class MemoizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Memoize
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

    public function testSet()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->get(
                function () use ($a, $b, &$called) {
                    $called++;

                    return $a + $b;
                }
            );
        };

        $closure = \Closure::bind($closure, null, $this->mem);

        $v1 = $closure(1, 2);
        $this->assertTrue($this->cache->contains('Sulu\Component\Util\{closure}([1,2])'));
        $this->assertEquals(3, $this->cache->fetch('Sulu\Component\Util\{closure}([1,2])'));
        $v2 = $closure(1, 2);

        $this->assertEquals(3, $v1);
        $this->assertEquals(3, $v2);

        $this->assertEquals(1, $called);

        $v1 = $closure(2, 3);
        $this->assertTrue($this->cache->contains('Sulu\Component\Util\{closure}([1,2])'));
        $this->assertEquals(3, $this->cache->fetch('Sulu\Component\Util\{closure}([1,2])'));
        $v2 = $closure(2, 3);
        $v3 = $closure(1, 2);

        $this->assertEquals(5, $v1);
        $this->assertEquals(5, $v2);
        $this->assertEquals(3, $v3);

        $this->assertEquals(2, $called);
    }
}
