<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Compat;

use Sulu\Component\Content\Compat\Property;

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function provideIsMultipleTest()
    {
        return [
            [0, 1, true],
            [0, 10, true],
            [1, 2, true],
            [2, null, true],
            [1, null, true],
            [null, 3, true],
            [1, 1, false],
            [null, null, false],
        ];
    }

    /**
     * @dataProvider provideIsMultipleTest
     */
    public function testIsMultipleTest($minOccurs, $maxOccurs, $result)
    {
        $property = new Property('test', [], 'text_line', false, true, $maxOccurs, $minOccurs);

        $this->assertEquals($result, $property->getIsMultiple());
    }
}
