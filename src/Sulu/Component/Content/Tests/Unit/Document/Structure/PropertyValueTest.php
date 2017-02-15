<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Unit\Document\Structure;

use Sulu\Component\Content\Document\Structure\PropertyValue;

/**
 * Tests for class PropertyValue.
 */
class PropertyValueTest extends \PHPUnit_Framework_TestCase
{
    public function provideOffsetSetData()
    {
        return [
            [[], 'foo', 'bar', ['foo' => 'bar']],
            [['foo' => 'bar'], 'foo', 'baz', ['foo' => 'baz']],
            [['foo' => ['bar']], 'foo', 'baz', ['foo' => 'baz']],
            [['foo' => ['bar']], 'foo', ['baz'], ['foo' => ['baz']]],
        ];
    }

    /**
     * @dataProvider provideOffsetSetData
     */
    public function testOffsetSet($value, $setName, $setValue, $expected)
    {
        $propertyValue = new PropertyValue('test', $value);

        $propertyValue[$setName] = $setValue;

        $this->assertEquals($propertyValue->getValue(), $expected);
    }
}
