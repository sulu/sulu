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

    public function setUp() {
        $this->navigationItem = new NavigationItem("NavigationItem");
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
}
