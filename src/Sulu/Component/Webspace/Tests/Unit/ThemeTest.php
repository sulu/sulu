<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

use Sulu\Component\Webspace\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $expected = ['key' => 'foo'];
        $theme = new Theme($expected['key']);

        $this->assertEquals($expected, $theme->toArray());
    }
}
