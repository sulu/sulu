<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\FrontView;

use DTL\Component\Content\FrontView\FrontViewIterator;
use DTL\Component\Content\FrontView\FrontView;
use Prophecy\PhpUnit\ProphecyTestCase;

class FrontViewTest extends ProphecyTestCase
{
    /**
     * @var FrontView
     */
    private $frontView;

    public function setUp()
    {
        parent::setUp();
        $this->frontView = new FrontView();
    }

    public function testValue()
    {
        $this->frontView->setValue('foo');
        $this->assertEquals('foo', $this->frontView->getValue('foo'));
        $this->assertEquals('foo', $this->frontView->__toString());
    }

    public function testAttribute()
    {
        $this->frontView->setAttribute('foo', 'bar');
        $this->assertEquals(
            'bar',
            $this->frontView->getAttribute('foo')
        );
    }

    public function provideChildren()
    {
        return array(
            array(
                array(
                    'one' => new FrontView(),
                    'two' => new FrontView(),
                ),
                null,
            ),
        );
    }

    /**
     * @dataProvider provideChildren
     */
    public function testChildren($children, $exception = null)
    {
        if ($exception) {
            $this->setExpectedException('\InvalidArgumentException', $exception);
        }

        $this->frontView->setChildren($children);
        $this->assertSame($children, (array) $this->frontView->getChildren());
        $this->assertCount(count($children), $this->frontView);

        foreach ($children as $childName => $child) {
            $this->assertEquals($child, $this->frontView[$childName]);
            $this->assertTrue(isset($this->frontView[$childName]));

            unset($this->frontView[$childName]);
            $this->assertFalse(isset($this->frontView[$childName]));
        }
    }

    /**
     * @dataProvider provideChildren
     */
    public function testIterator($children)
    {
        $this->frontView->setChildren($children);

        foreach ($this->frontView as $child) {
            $expectedChild = array_shift($children);
            $this->assertSame($expectedChild, $child);
        }
    }
}
