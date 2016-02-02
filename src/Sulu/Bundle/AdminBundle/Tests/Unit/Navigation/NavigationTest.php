<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Navigation;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class NavigationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Navigation
     */
    protected $navigation1;
    /**
     * @var NavigationItem
     */
    protected $root1;
    /**
     * @var Navigation
     */
    protected $navigation2;
    /**
     * @var NavigationItem
     */
    protected $root2;

    public function setUp()
    {
        //Setup first navigation
        $this->root1 = new NavigationItem('Root');
        $item1_1 = new NavigationItem('Portals', $this->root1);
        $item1_1->setPosition(1);
        (new NavigationItem('DE', $item1_1))->setPosition(1);
        (new NavigationItem('AT', $item1_1))->setPosition(2);
        (new NavigationItem('COM', $item1_1))->setPosition(3);
        $item1_2 = new NavigationItem('Settings', $this->root1);
        $item1_2->setPosition(2);
        (new NavigationItem('Translate', $item1_2))->setPosition(1);

        $this->navigation1 = new Navigation($this->root1);

        //Setup second navigation
        $this->root2 = new NavigationItem('Root');
        $item1_1 = new NavigationItem('Portals', $this->root2);
        new NavigationItem('IT', $item1_1);
        new NavigationItem('ES', $item1_1);
        new NavigationItem('FR', $item1_1);
        $item1_2 = new NavigationItem('Settings', $this->root2);
        $item2_1 = new NavigationItem('Translate', $item1_2);
        new NavigationItem('Advanced', $item2_1);
        $item2_2 = new NavigationItem('Shop', $item1_2);
        new NavigationItem('Shipping', $item2_2);
        new NavigationItem('Globals', $this->root2);
        $this->navigation2 = new Navigation($this->root2);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->root1, $this->navigation1->getRoot());
    }

    public function testMerge()
    {
        $merged = $this->navigation1->merge($this->navigation2);

        $children1 = $merged->getRoot()->getChildren();
        $this->assertEquals('Portals', $children1[0]->getName());
        $this->assertEquals('Settings', $children1[1]->getName());
        $this->assertEquals('Globals', $children1[2]->getName());

        $children2_1 = $children1[0]->getChildren();
        $this->assertEquals('DE', $children2_1[0]->getName());
        $this->assertEquals('AT', $children2_1[1]->getName());
        $this->assertEquals('COM', $children2_1[2]->getName());
        $this->assertEquals('IT', $children2_1[3]->getName());
        $this->assertEquals('ES', $children2_1[4]->getName());
        $this->assertEquals('FR', $children2_1[5]->getName());

        $children2_2 = $children1[1]->getChildren();
        $this->assertEquals('Translate', $children2_2[0]->getName());
        $this->assertEquals('Shop', $children2_2[1]->getName());

        $children3_1 = $children2_2[0]->getChildren();
        $this->assertEquals('Advanced', $children3_1[0]->getName());

        $children3_2 = $children2_2[1]->getChildren();
        $this->assertEquals('Shipping', $children3_2[0]->getName());
    }

    public function testToArray()
    {
        $array = $this->navigation1->toArray();

        $this->assertEquals('Root', $array['title']);
        $this->assertEquals('Portals', $array['items'][0]['title']);
        $this->assertEquals('DE', $array['items'][0]['items'][0]['title']);
        $this->assertEquals('AT', $array['items'][0]['items'][1]['title']);
        $this->assertEquals('COM', $array['items'][0]['items'][2]['title']);
        $this->assertEquals('Settings', $array['items'][1]['title']);
        $this->assertEquals('Translate', $array['items'][1]['items'][0]['title']);
    }
}
