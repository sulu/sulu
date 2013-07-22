<?php
/**
 * Created by JetBrains PhpStorm.
 * User: danielrotter
 * Date: 22.07.13
 * Time: 10:08
 * To change this template use File | Settings | File Templates.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Navigation;

use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class NavigationItemTest extends \PHPUnit_Framework_TestCase {
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

    public function setUp() {
        $this->navigationItem = new NavigationItem("NavigationItem");

        $this->item1 = new NavigationItem('Root');
        new NavigationItem('Portals', $this->item1);
        new NavigationItem('Settings', $this->item1);
        $this->item2 = new NavigationItem('Root');
        new NavigationItem('Portals', $this->item2);
        new NavigationItem('Settings', $this->item2);
        new NavigationItem('Globals', $this->item2);
    }

    public function testConstructor() {
        $this->assertEquals("NavigationItem", $this->navigationItem->getName());

        $item = new NavigationItem('ChildItem', $this->navigationItem);
        $this->assertEquals($item, $this->navigationItem->getChildren()[0]);
    }

    public function testName() {
        $this->navigationItem->setName("OtherNavigationItem");
        $this->assertEquals("OtherNavigationItem", $this->navigationItem->getName());
    }

    public function testChildren() {
        $child = new NavigationItem("Child");
        $this->navigationItem->addChild($child);
        $this->assertEquals($child, $this->navigationItem->getChildren()[0]);
    }

    public function testSearch() {
        $this->assertEquals('Globals', $this->item2->find(new NavigationItem('Globals'))->getName());
        $this->assertNull($this->item1->find(new NavigationItem('Nothing')));
    }

    public function testMerge() {
        $merged = $this->item1->merge($this->item2);

        $this->assertEquals('Root', $merged->getName());
        $mergedChildren = $merged->getChildren();
        $this->assertEquals('Portals', $mergedChildren[0]->getName());
        $this->assertEquals('Settings', $mergedChildren[1]->getName());
        $this->assertEquals('Globals', $mergedChildren[2]->getName());
    }

    public function testIterator() {
        $array = array();
        foreach ($this->item2 as $key => $value) {
            $array[$key] = $value;
        }

        $this->assertEquals('Portals', $array[0]);
        $this->assertEquals('Settings', $array[1]);
        $this->assertEquals('Globals', $array[2]);
    }
}
