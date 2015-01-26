<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\Form;

use DTL\Component\Content\Form\ContentViewIterator;
use DTL\Component\Content\Form\ContentView;
use Prophecy\PhpUnit\ProphecyTestCase;

class ContentViewTest extends ProphecyTestCase
{
    /**
     * @var ContentView
     */
    private $contentView;

    public function setUp()
    {
        parent::setUp();
        $this->contentView = new ContentView();
    }

    public function testValue()
    {
        $this->contentView->setValue('foo');
        $this->assertEquals('foo', $this->contentView->getValue('foo'));
        $this->assertEquals('foo', $this->contentView->__toString());
    }

    public function testAttribute()
    {
        $this->contentView->setAttribute('foo', 'bar');
        $this->assertEquals(
            'bar',
            $this->contentView->getAttribute('foo')
        );
    }

    public function provideChildren()
    {
        return array(
            array(
                array(
                    'one' => new ContentView(),
                    'two' => new ContentView(),
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

        $this->contentView->setChildren($children);
        $this->assertSame($children, (array) $this->contentView->getChildren());
        $this->assertCount(count($children), $this->contentView);

        foreach ($children as $childName => $child) {
            $this->assertEquals($child, $this->contentView[$childName]);
            $this->assertTrue(isset($this->contentView[$childName]));

            unset($this->contentView[$childName]);
            $this->assertFalse(isset($this->contentView[$childName]));
        }
    }

    /**
     * @dataProvider provideChildren
     */
    public function testIterator($children)
    {
        $this->contentView->setChildren($children);

        foreach ($this->contentView as $child) {
            $expectedChild = array_shift($children);
            $this->assertSame($expectedChild, $child);
        }
    }
}
