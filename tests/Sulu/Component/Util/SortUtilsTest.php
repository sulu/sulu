<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Util;

use Symfony\Component\PropertyAccess\PropertyAccess;

class SortUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function provideSortObjects()
    {
        return array(
            // ascending
            array(
                array(
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ),
                'getField1',
                'asc',
                'field1', array('aaa', 'bbb', 'ccc'),
            ),

            // descending
            array(
                array(
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ),
                'getField1',
                'desc',
                'field1', array('ccc', 'bbb', 'aaa'),
            ),

            // sort on a different method
            array(
                array(
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'bbb'),
                    new FoobarTestClass('aaa', 'ccc'),
                ),
                'getField2',
                'desc',
                'field1', array('aaa', 'bbb', 'ccc'),
            ),

            // sort by two methods
            array(
                array(
                    new FoobarTestClass('ccc', 'aaa'),
                    new FoobarTestClass('bbb', 'aaa'),
                    new FoobarTestClass('aaa', 'aaa'),
                ),
                array('getField2', 'getField1'),
                'asc',
                'field1', array('aaa', 'bbb', 'ccc'),
            ),

            // check sorting numerical strings
            array(
                array(
                    new FoobarTestClass('15', 'aaa'),
                    new FoobarTestClass('100', 'bbb'),
                    new FoobarTestClass('200', 'ccc'),
                ),
                'getField1',
                'asc',
                'field1', array('15', '100', '200'),
            ),

            // check array
            array(
                array(
                    array('foo' => 'zzz', 'bar' => 'aaa'),
                    array('foo' => 'xxx', 'bar' => 'aaa'),
                    array('foo' => 'yyy', 'bar' => 'aaa'),
                ),
                '[foo]',
                'asc',
                '[foo]', array('xxx', 'yyy', 'zzz'),
            ),

            // multi dimensional array
            array(
                array(
                    array('foo' => '1', 'baz' => array('bar' => 'bbb')),
                    array('foo' => '2', 'baz' => array('bar' => 'aaa')),
                    array('foo' => '3', 'baz' => array('bar' => 'ccc')),
                ),
                '[baz][bar]',
                'asc',
                '[foo]', array('2', '1', '3'),
            ),

            // multi dimensional array missing key
            array(
                array(
                    array('foo' => '1', 'baz' => array('bar' => 'bbb')),
                    array('foo' => '2', 'baz' => array('sad' => 'aaa')),
                    array('foo' => '3', 'baz' => array('bad' => 'ccc')),
                ),
                '[baz][bar]',
                'asc',
                '[foo]', array('3', '2', '1'),
            ),
        );
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
        $collection = new \ArrayObject(array(
            new FoobarTestClass('value2', 'value2'),
            new FoobarTestClass('value1', 'value2'),
        ));

        $res = SortUtils::multisort($collection, 'getField1');

        $this->assertEquals('value1', $res[0]->field1);
        $this->assertEquals('value2', $res[0]->field2);
    }

    /**
     * @expectedException Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException
     */
    public function testSortMissingField()
    {
        $collection = new \ArrayObject(array(
            (object) array('value2', 'value2'),
            (object) array('value1', 'value2'),
        ));

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
