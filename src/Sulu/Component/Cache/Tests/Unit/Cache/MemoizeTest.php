<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Cache\Tests\Unit\Cache;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Sulu\Component\Cache\Memoize;
use Sulu\Component\Cache\MemoizeInterface;

class MemoizeTest extends TestCase
{
    private MemoizeInterface $mem;

    private int $defaultLifeTime = 600;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mem = new Memoize(new ArrayAdapter(), $this->defaultLifeTime);
    }

    public function testMemoizeByIdFirstCall(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdFirstCallWithLifeTime(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function($a, $b) use (&$called) {
                    ++$called;

                    return $a + $b;
                },
                100
            );
        };

        $v1 = $closure(1, 2);
        $v2 = $closure(2, 3);

        $this->assertEquals(3, $v1);
        $this->assertEquals(5, $v2);

        $this->assertEquals(2, $called);
    }

    public function testMemoizeByIdSecondCall(): void
    {
        $mem = $this->mem;
        $called = 0;
        $closure = function($a, $b) use ($mem, &$called) {
            return $mem->memoizeById(
                'mem',
                [$a, $b],
                function() use ($a, $b, &$called) {
                    ++$called;

                    return $a + $b;
                }
            );
        };

        // First call - should execute the closure and cache the result.
        $v1First = $closure(1, 2);
        $v2First = $closure(2, 3);

        $this->assertEquals(2, $called);

        // Second call - should use cached values, closure should not be called.
        $called = 0;
        $v1Second = $closure(1, 2);
        $v2Second = $closure(2, 3);

        $this->assertEquals(3, $v1Second);
        $this->assertEquals(5, $v2Second);
        $this->assertEquals(0, $called);
    }
}
