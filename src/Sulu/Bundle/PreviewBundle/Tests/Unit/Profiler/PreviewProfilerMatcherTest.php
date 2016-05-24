<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Profiler;

use Sulu\Bundle\PreviewBundle\Profiler\PreviewProfilerMatcher;
use Symfony\Component\HttpFoundation\Request;

class PreviewProfilerMatcherTest extends \PHPUnit_Framework_TestCase
{
    public function matchesDataProvider()
    {
        return [
            [true, true],
            [false, false],
            [null, false],
        ];
    }

    /**
     * @dataProvider matchesDataProvider
     */
    public function testMatches($value, $expected)
    {
        $matcher = new PreviewProfilerMatcher();
        $request = new Request([], [], ['_profiler' => $value]);

        $this->assertEquals($expected, $matcher->matches($request));
    }
}
