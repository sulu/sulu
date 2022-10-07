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

    public function testConstructor(): void
    {
        $this->assertEquals('NavigationItem', $this->navigationItem->getName());

        $item = new NavigationItem('ChildItem');
        $this->navigationItem->addChild($item);
        $this->assertEquals($item, $this->navigationItem->getChildren()[0]);
    }

    public function testId(): void
    {
        $this->navigationItem->setId('test');
        $this->assertEquals('test', $this->navigationItem->getId());
    }

    public function testName(): void
    {
        $this->navigationItem->setName('OtherNavigationItem');
        $this->assertEquals('OtherNavigationItem', $this->navigationItem->getName());
    }

    public function testLabel(): void
    {
        $this->navigationItem->setLabel('label');
        $this->assertEquals('label', $this->navigationItem->getLabel());
    }

    public function testIcon(): void
    {
        $this->navigationItem->setIcon('icon');
        $this->assertEquals('icon', $this->navigationItem->getIcon());
    }

    public function testView(): void
    {
        $this->navigationItem->setView('view');
        $this->assertEquals('view', $this->navigationItem->getView());
    }

    public function testPosition(): void
    {
        $this->navigationItem->setPosition(110);
        $this->assertEquals(110, $this->navigationItem->getPosition());
    }

    public function testDisabled(): void
    {
        $this->navigationItem->setDisabled(true);
        $this->assertEquals(true, $this->navigationItem->getDisabled());
    }

    public function testVisible(): void
    {
        $this->navigationItem->setVisible(false);
        $this->assertEquals(false, $this->navigationItem->getVisible());
    }

    public function testChildren(): void
    {
        $child = new NavigationItem('Child');
        $this->navigationItem->addChild($child);
        $this->assertEquals($child, $this->navigationItem->getChildren()[0]);
    }

    public function testFind(): void
    {
        $this->assertEquals('Globals', $this->item2->find(new NavigationItem('Globals'))->getName());
        $this->assertNull($this->item1->find(new NavigationItem('Nothing')));
    }

    public function testHasChildren(): void
    {
        $this->assertTrue($this->item1->hasChildren());
        $this->assertFalse($this->navigationItem->hasChildren());
    }

    public function testFindChildren(): void
    {
        $this->assertEquals('Portals', $this->item1->findChildren(new NavigationItem('Portals'))->getName());
        $this->assertNull($this->navigationItem->findChildren(new NavigationItem('Nothing')));
    }

    public function testCopyChildless(): void
    {
        $copy = $this->item1->copyChildless();

        $this->assertEquals($this->item1->getIcon(), $copy->getIcon());
        $this->assertEquals($this->item1->getId(), $copy->getId());
    }

    public function testIterator(): void
    {
        $array = [];
        foreach ($this->item2 as $key => $value) {
            $array[$key] = $value;
        }

        $this->assertEquals('Portals', $array[0]->getName());
        $this->assertEquals('Settings', $array[1]->getName());
        $this->assertEquals('Globals', $array[2]->getName());
    }

    public function testToArray(): void
    {
        $array = $this->item1->toArray();

        $this->assertEquals('Root', $array['title']);

        $this->assertContains('Portals', [$array['items'][0]['title'], $array['items'][1]['title']]);

        $this->assertContains('Settings', [$array['items'][0]['title'], $array['items'][1]['title']]);

        $array = $this->item2->toArray();

        $this->assertNotContains('header', \array_keys($array));
    }

    public function testToArrayWithoutChildre(): void
    {
        $item = new NavigationItem('Navigation Item');
        $item->setId('test-id');
        $item->setLabel('test-label');
        $item->setIcon('test-icon');
        $item->setView('test-view');

        $this->assertEquals([
            'title' => 'Navigation Item',
            'label' => 'test-label',
            'icon' => 'test-icon',
            'view' => 'test-view',
            'id' => 'test-id',
            'disabled' => false,
            'visible' => true,
        ], $item->toArray());
    }

    public function testToArrayWithPosition(): void
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
