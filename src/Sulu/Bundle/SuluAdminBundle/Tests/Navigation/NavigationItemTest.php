<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART Webservices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Navigation;

use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class NavigationItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NavigationItem
     */
    protected $navigationItem;
    /**
     * @var NavigationItem
     */
    protected $item1;
    /**
     * @var NavigationItem
     */
    protected $item2;

    public function setUp()
    {
        $this->navigationItem = new NavigationItem('NavigationItem');

        $this->item1 = new NavigationItem('Root');
        $this->item1->setAction('action');
        new NavigationItem('Portals', $this->item1);
        new NavigationItem('Settings', $this->item1);
        $this->item2 = new NavigationItem('Root');
        new NavigationItem('Portals', $this->item2);
        new NavigationItem('Settings', $this->item2);
        new NavigationItem('Globals', $this->item2);
    }

    public function testConstructor()
    {
        $this->assertEquals('NavigationItem', $this->navigationItem->getName());

        $item = new NavigationItem('ChildItem', $this->navigationItem);
        $this->assertEquals($item, $this->navigationItem->getChildren()[0]);
    }

    public function testId()
    {
        $this->navigationItem->setId('test');
        $this->assertEquals('test', $this->navigationItem->getId());
    }

    public function testName()
    {
        $this->navigationItem->setName('OtherNavigationItem');
        $this->assertEquals("OtherNavigationItem", $this->navigationItem->getName());
    }

    public function testAction()
    {
        $this->navigationItem->setAction('/test/action');
        $this->assertEquals('/test/action', $this->navigationItem->getAction());
    }

    public function testChildren()
    {
        $child = new NavigationItem('Child');
        $this->navigationItem->addChild($child);
        $this->assertEquals($child, $this->navigationItem->getChildren()[0]);
    }

    public function testSearch()
    {
        $this->assertEquals('Globals', $this->item2->find(new NavigationItem('Globals'))->getName());
        $this->assertNull($this->item1->find(new NavigationItem('Nothing')));
    }

    public function testMerge()
    {
        $merged = $this->item1->merge($this->item2);

        $this->assertEquals('Root', $merged->getName());
        $mergedChildren = $merged->getChildren();
        $this->assertEquals('Portals', $mergedChildren[0]->getName());
        $this->assertEquals('Settings', $mergedChildren[1]->getName());
        $this->assertEquals('Globals', $mergedChildren[2]->getName());
    }

    public function testHasChildren()
    {
        $this->assertTrue($this->item1->hasChildren());
        $this->assertFalse($this->navigationItem->hasChildren());
    }

    public function testIterator()
    {
        $array = array();
        foreach ($this->item2 as $key => $value) {
            $array[$key] = $value;
        }

        $this->assertEquals('Portals', $array[0]->getName());
        $this->assertEquals('Settings', $array[1]->getName());
        $this->assertEquals('Globals', $array[2]->getName());
    }

    public function testToArray()
    {
        $array = $this->item1->toArray();

        $this->assertEquals('Root', $array['title']);
        $this->assertTrue($array['hasSub']);
        $this->assertEquals('action', $array['action']);

        $this->assertEquals('Portals', $array['sub']['items'][0]['title']);
        $this->assertFalse($array['sub']['items'][0]['hasSub']);
        $this->assertEquals(null, $array['sub']['items'][0]['action']);

        $this->assertEquals('Settings', $array['sub']['items'][1]['title']);
        $this->assertFalse($array['sub']['items'][1]['hasSub']);
        $this->assertEquals(null, $array['sub']['items'][1]['action']);
    }
}
