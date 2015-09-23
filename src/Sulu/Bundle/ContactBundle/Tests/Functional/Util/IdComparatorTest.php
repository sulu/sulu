<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Util;

class IdComparatorTest extends \PHPUnit_Framework_TestCase
{
    public function usortProvider()
    {
        return [
            [[5, 4, 3, 1, 2], [1, 2, 3, 4, 5], [1, 2, 3, 4, 5]],
            [[5, 4, 3, 1, 2, 7], [1, 2, 3, 5, 6], [1, 2, 3, 5, 7, 4]],
            [[5, 4, 3, 1, 2], [1, 2, 5], [1, 2, 5, 3, 4]],
            [[5, 1, 2], [1, 2, 3, 4, 5], [1, 2, 5]],
            [['a5', 'b1', 'c2'], ['b1', 'c2', 'a5'], ['b1', 'c2', 'a5']],
            [['a5', 'c2'], ['b1', 'c2', 'a5'], ['c2', 'a5']],
            [['a5', 'b1', 'c2'], ['b1', 'a5'], ['b1', 'a5', 'c2']],
            [['a5', 'b1', 'c2', 'a1'], ['b1', 'a5', 'd1'], ['b1', 'a5', 'a1', 'c2']],
            [['a5', 'b1', 'c2', 11], ['b1', 11, 'c2', 'a5'], ['b1', 11, 'c2', 'a5']],
            [['a5', 'b1', 'c2', 11, 14], ['b1', 11, 'c2', 'a5'], ['b1', 11, 'c2', 'a5', 14]],
            [['a5', 'b1', 'c2', 11, 14], ['b1', 11, 'c2', 'a5', 15], ['b1', 11, 'c2', 'a5', 14]],
            [['a5', 'b1', 'c2', 11], ['b1', 11, 'c2', 'a5', 15], ['b1', 11, 'c2', 'a5']],
        ];
    }

    /**
     * @dataProvider usortProvider
     */
    public function testUsortCallback($array, $ids, $expected)
    {
        $comparator = new IdComparator();
        usort(
            $array,
            function ($a, $b) use ($ids, $comparator) {
                return $comparator->compare($a, $b, $ids);
            }
        );

        $this->assertEquals($expected, $array);
    }
}
