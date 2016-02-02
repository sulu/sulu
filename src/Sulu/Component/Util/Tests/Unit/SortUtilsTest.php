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

use Sulu\Component\Util\SortUtils;
use Symfony\Component\PropertyAccess\PropertyAccess;

class SortUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function provideSortObjects()
    {
        return [
            // ascending
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                'getField1',
                'asc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // descending
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                'getField1',
                'desc',
                'field1', ['ccc', 'bbb', 'aaa'],
            ],

            // sort on a different method
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'bbb'),
                    new FoobarTestClass('aaa', 'ccc'),
                ],
                'getField2',
                'desc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // sort by two methods
            [
                [
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ],
                ['getField2', 'getField1'],
                'asc',
                'field1', ['aaa', 'bbb', 'ccc'],
            ],

            // check sorting numerical strings
            [
                [
                    new FoobarTestClass('15', 'aaa'),
                    new FoobarTestClass('100', 'bbb'),
                    new FoobarTestClass('200', 'ccc'),
                ],
                'getField1',
                'asc',
                'field1', ['15', '100', '200'],
            ],

            // check array
            [
                [
                    ['foo' => 'zzz', 'bar' => 'aaa'],
                    ['foo' => 'xxx', 'bar' => 'aaa'],
                    ['foo' => 'yyy', 'bar' => 'aaa'],
                ],
                '[foo]',
                'asc',
                '[foo]', ['xxx', 'yyy', 'zzz'],
            ],

            // multi dimensional array
            [
                [
                    ['foo' => '1', 'baz' => ['bar' => 'bbb']],
                    ['foo' => '2', 'baz' => ['bar' => 'aaa']],
                    ['foo' => '3', 'baz' => ['bar' => 'ccc']],
                ],
                '[baz][bar]',
                'asc',
                '[foo]', ['2', '1', '3'],
            ],

            // multi dimensional array missing key
            [
                [
                    ['foo' => '1', 'baz' => ['bar' => 'bbb']],
                    ['foo' => '2', 'baz' => ['sad' => 'aaa']],
                    ['foo' => '3', 'baz' => ['bad' => 'ccc']],
                ],
                '[baz][bar]',
                'asc',
                '[foo]', ['3', '2', '1'],
            ],
        ];
    }

    /**
     * @dataProvider provideSortObjects
     */
    public function testSortObjects($data, $methodName, $direction, $checkField, $expectedOrder)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $res = SortUtils::multisort($data, $methodName, $direction);

        foreach ($expectedOrder as $expected) {
            $object = array_shift($res);
            $this->assertEquals($expected, $accessor->getValue($object, $checkField));
        }
    }

    public function testSortArrayObject()
    {
        $collection = new \ArrayObject([
            new FoobarTestClass('value2', 'value2'),
            new FoobarTestClass('value1', 'value2'),
        ]);

        $res = SortUtils::multisort($collection, 'getField1');

        $this->assertEquals('value1', $res[0]->field1);
        $this->assertEquals('value2', $res[0]->field2);
    }

    /**
     * @expectedException Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSortMissingField()
    {
        $collection = new \ArrayObject([
            (object) ['value2', 'value2'],
            (object) ['value1', 'value2'],
        ]);

        SortUtils::multisort($collection, 'somefink');
    }
}

class FoobarTestClass
{
    public $field1;
    public $field2;

    public function __construct($value1, $value2)
    {
        $this->field1 = $value1;
        $this->field2 = $value2;
    }

    public function getField1()
    {
        return $this->field1;
    }

    public function getField2()
    {
        return $this->field2;
    }
}
