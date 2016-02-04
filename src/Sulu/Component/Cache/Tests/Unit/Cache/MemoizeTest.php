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

use Doctrine\Common\Cache\Cache;
use Sulu\Component\Cache\Memoize;

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

    /**
     * @var int
     */
    private $defaultLifeTime = 600;

    protected function setUp()
    {
        parent::setUp();

        $this->cache = $this->prophesize('Doctrine\Common\Cache\Cache');

        $this->mem = new Memoize($this->cache->reveal(), $this->defaultLifeTime);
    }

    public function testMemoizeFirstCall()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoize(
                function ($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([1, 2])));
        $id23 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([2, 3])));

        $this->cache->save($id12, 3, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeFirstCallWithLifeTime()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoize(
                function ($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                },
                100
            );
        };

        $id12 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([1, 2])));
        $id23 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([2, 3])));

        $this->cache->save($id12, 3, 100)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, 100)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeSecondCall()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoize(
                function ($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([1, 2])));
        $id23 = md5(sprintf('%s::%s(%s)', __CLASS__, 'Sulu\Component\Cache\Tests\Unit\{closure}', serialize([2, 3])));

        $this->cache->fetch($id12)->wilLReturn(3);
        $this->cache->contains($id12)->willReturn(true);

        $this->cache->fetch($id23)->wilLReturn(5);
        $this->cache->contains($id23)->willReturn(true);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(0, $called);
    }

    public function testMemoizeByIdFirstCall()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function ($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = md5(sprintf('mem(%s)', serialize([1, 2])));
        $id23 = md5(sprintf('mem(%s)', serialize([2, 3])));

        $this->cache->save($id12, 3, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, $this->defaultLifeTime)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdFirstCallWithLifeTime()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function ($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                },
                100
            );
        };

        $id12 = md5(sprintf('mem(%s)', serialize([1, 2])));
        $id23 = md5(sprintf('mem(%s)', serialize([2, 3])));

        $this->cache->save($id12, 3, 100)->willReturn(null);
        $this->cache->contains($id12)->willReturn(false);

        $this->cache->save($id23, 5, 100)->willReturn(null);
        $this->cache->contains($id23)->willReturn(false);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdSecondCall()
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function ($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function () use ($a, $b, &$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $id12 = md5(sprintf('mem(%s)', serialize([1, 2])));
        $id23 = md5(sprintf('mem(%s)', serialize([2, 3])));

        $this->cache->fetch($id12)->wilLReturn(3);
        $this->cache->contains($id12)->willReturn(true);

        $this->cache->fetch($id23)->wilLReturn(5);
        $this->cache->contains($id23)->willReturn(true);

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(0, $called);
    }
}
