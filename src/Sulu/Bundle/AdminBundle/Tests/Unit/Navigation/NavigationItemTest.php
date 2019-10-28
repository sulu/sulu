<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Navigation;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;

class NavigationItemTest extends TestCase
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

    public function setUp(): void
    {
        $this->navigationItem = new NavigationItem('NavigationItem');

        $this->item1 = new NavigationItem('Root');

        $portalItem1 = new NavigationItem('Portals');
        $this->item1->addChild($portalItem1);
        $settingsItem1 = new NavigationItem('Settings');
        $this->item1->addChild($settingsItem1);

        $this->item2 = new NavigationItem('Root');
        $portalItem2 = new NavigationItem('Portals');
        $this->item2->addChild($portalItem2);
        $settingsItem2 = new NavigationItem('Settings');
        $this->item2->addChild($settingsItem2);
        $globalItem2 = new NavigationItem('Globals');
        $this->item2->addChild($globalItem2);
    }

    public function testConstructor()
    {
        $this->assertEquals('NavigationItem', $this->navigationItem->getName());

        $item = new NavigationItem('ChildItem');
        $this->navigationItem->addChild($item);
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
        $this->assertEquals('OtherNavigationItem', $this->navigationItem->getName());
    }

    public function testIcon()
    {
        $this->navigationItem->setIcon('icon');
        $this->assertEquals('icon', $this->navigationItem->getIcon());
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

    public function testHasChildren()
    {
        $this->assertTrue($this->item1->hasChildren());
        $this->assertFalse($this->navigationItem->hasChildren());
    }

    public function testCopyChildless()
    {
        $copy = $this->item1->copyChildless();

        $this->assertEquals($this->item1->getIcon(), $copy->getIcon());
        $this->assertEquals($this->item1->getId(), $copy->getId());
    }

    public function testIterator()
    {
        $array = [];
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

        $this->assertContains('Portals', [$array['items'][0]['title'], $array['items'][1]['title']]);

        $this->assertContains('Settings', [$array['items'][0]['title'], $array['items'][1]['title']]);

        $array = $this->item2->toArray();

        $this->assertNotContains('header', array_keys($array));
    }

    public function testToArrayWithPosition()
    {
        $rootNavigationItem = new NavigationItem('Root');
        $navigationItem2 = new NavigationItem('Item 2');
        $navigationItem2->setPosition(10);
        $navigationItem1 = new NavigationItem('Item 1');
        $navigationItem1->setPosition(0);
        $rootNavigationItem->addChild($navigationItem1);
        $rootNavigationItem->addChild($navigationItem2);

        $array = $rootNavigationItem->toArray();

        $this->assertEquals('Item 1', $array['items'][0]['title']);
        $this->assertEquals('Item 2', $array['items'][1]['title']);
    }
}
