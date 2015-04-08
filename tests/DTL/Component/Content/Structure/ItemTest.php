<?php

namespace DTL\Component\Content\Structure;

use Prophecy\PhpUnit\ProphecyTestCase;

class ItemTest extends ProphecyTestCase
{
    private $item;

    public function getItem()
    {
        return new Item('test');
    }

    public function testGetChildren()
    {
        $item = $this->getItem();
        $children = array(
            'item_1' => new Item('item_1'),
            'item_2' => new Item('item_2'),
        );
        $item->addChild($children['item_1']);
        $item->addChild($children['item_2']);

        $this->assertEquals($children, $item->getChildren());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testAddChildDuplicate()
    {
        $item = $this->getItem();
        $item->addChild(new Item('prop_2'));
        $item->addChild(new Item('prop_2'));
    }

    public function testGetLocalizedTitle()
    {
        $item = $this->getItem();
        $child = new Item('prop_1');
        $child->title = array(
            'fr' => 'French',
            'de' => 'German',
        );
        $item->addChild($child);

        $this->assertEquals('French', $child->getLocalizedTitle('fr'));
    }

    public function testGetLocalizedNotKnown()
    {
        $item = $this->getItem();
        $child = new Item('prop_1');
        $child->title = array(
            'fr' => 'French',
        );

        $this->assertEquals('Prop_1', $child->getLocalizedTitle('de'));
    }

    public function testHasChild()
    {
        $item = $this->getItem();
        $item->addChild(new Item('prop_1'));
        $this->assertTrue($item->hasChild('prop_1'));
    }

    public function testHasChildFalse()
    {
        $item = $this->getItem();
        $item->addChild(new Item('prop_1'));
        $this->assertFalse($item->hasChild('prop_1243'));
    }
}
