<?php

namespace Sulu\Component\Util;

class TextUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function provideTruncate()
    {
        return array(
            array('Hello', 10, null, 'Hello'),
            array('Hello this is some text', 10, '...', 'Hello t...'),
            array('Hello this is some text', 10, '-', 'Hello thi-'),
            array(
                'Dorn, Oberbergischer Kreis, Regierungsbezirk Köln, Nordrhein-Westfalen, Deutschland, European Union',
                50,
                '...',
                'Dorn, Oberbergischer Kreis, Regierungsbezirk Kö...'
            ),
        );
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
