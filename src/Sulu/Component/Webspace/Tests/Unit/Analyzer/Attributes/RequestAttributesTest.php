<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Analyzer\Attributes;

use Sulu\Component\Webspace\Analyzer\Attributes\RequestAttributes;

class RequestAttributesTest extends \PHPUnit_Framework_TestCase
{
    public function provideData()
    {
        return [
            [['test1' => 1], 'test1', null, 1],
            [['test1' => 1], 'test2', null, null],
            [['test1' => 1], 'test2', 2, 2],
            [['test1' => 1, 'test2' => 2], 'test2', null, 2],
            [['test1' => 1, 'test2' => 2], 'test2', 2, 2],
            [['test1' => 1, 'test2' => 2], 'test2', 3, 2],
        ];
    }

    /**
     * @dataProvider provideData
     */
    public function testGetAttribute(array $attributes, $name, $default, $expected)
    {
        $instance = new RequestAttributes($attributes);

        $this->assertEquals($expected, $instance->getAttribute($name, $default));
    }

    public function testMerge()
    {
        $instance1 = new RequestAttributes();
        $instance2 = new RequestAttributes(['test1' => 1]);
        $instance3 = new RequestAttributes(['test1' => 2, 'test2' => 3]);

        $result1 = $instance1->merge($instance2);
        $this->assertNotSame($result1, $instance1);
        $this->assertNotSame($result1, $instance2);

        $this->assertEquals(1, $result1->getAttribute('test1'));

        $result2 = $result1->merge($instance3);
        $this->assertNotSame($result2, $result1);
        $this->assertNotSame($result2, $instance1);
        $this->assertNotSame($result2, $instance2);
        $this->assertNotSame($result2, $instance3);

        $this->assertEquals(1, $result2->getAttribute('test1'));
        $this->assertEquals(3, $result2->getAttribute('test2'));
        $this->assertEquals(null, $result2->getAttribute('test3'));
    }
}
