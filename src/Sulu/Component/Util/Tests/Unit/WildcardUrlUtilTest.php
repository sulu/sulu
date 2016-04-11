<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util\Tests\Unit;

use Sulu\Component\Util\WildcardUrlUtil;

class WildcardUrlUtilTest extends \PHPUnit_Framework_TestCase
{
    public function provideMatchData()
    {
        return [
            ['*.sulu.lo', '1.sulu.lo', true],
            ['*.sulu.lo', '1.sulu.lo/test', true],
            ['*.sulu.lo', '1.sulu.com', false],
            ['*.sulu.lo', '1.sulu.com/test', false],
            ['1.sulu.lo', '1.sulu.lo', true],
            ['1.sulu.lo', '1.sulu.lo/test', true],
            ['1.sulu.lo', '1.sulu.com', false],
        ];
    }

    /**
     * @dataProvider provideMatchData
     */
    public function testMatch($portalUrl, $url, $expected)
    {
        $this->assertEquals($expected, WildcardUrlUtil::match($url, $portalUrl));
    }

    public function provideResolveData()
    {
        return [
            ['*.sulu.lo', '1.sulu.lo', '1.sulu.lo'],
            ['*.sulu.lo', '1.sulu.lo/test', '1.sulu.lo'],
            ['*.sulu.lo/*', '1.sulu.lo/test', '1.sulu.lo/test'],
            ['*.sulu.lo/*', '1.sulu.lo/test/asdf', '1.sulu.lo/test'],
            ['*.sulu.lo/*/*', '1.sulu.lo/test', null],
            ['*.sulu.lo/*/*', '1.sulu.lo/test/asdf', '1.sulu.lo/test/asdf'],
            ['*.sulu.lo/*/*', '1.sulu.lo/test/asdf/qwertz', '1.sulu.lo/test/asdf'],
            ['*.sulu.lo', 'sulu.lo', null],
        ];
    }

    /**
     * @dataProvider provideResolveData
     */
    public function testResolve($portalUrl, $url, $expected)
    {
        $this->assertEquals($expected, WildcardUrlUtil::resolve($url, $portalUrl));
    }
}
