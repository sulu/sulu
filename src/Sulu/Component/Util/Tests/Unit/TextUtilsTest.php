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

use Sulu\Component\Util\TextUtils;

class TextUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function provideTruncate()
    {
        return [
            ['Hello', 10, null, 'Hello'],
            ['Hello this is some text', 10, '...', 'Hello t...'],
            ['Hello this is some text', 10, '-', 'Hello thi-'],
            [
                'Dorn, Oberbergischer Kreis, Regierungsbezirk Köln, Nordrhein-Westfalen, Deutschland, European Union',
                50,
                '...',
                'Dorn, Oberbergischer Kreis, Regierungsbezirk Kö...',
            ],
        ];
    }

    /**
     * @dataProvider provideTruncate
     */
    public function testTruncate($text, $length, $suffix, $expected)
    {
        $res = TextUtils::truncate($text, $length, $suffix);
        $this->assertEquals($expected, $res);
    }
}
