<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\Admin\Navigation;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Exception\NavigationItemNotFoundException;

class NavigationItemCollectionTest extends TestCase
{
    public function testGet()
    {
        $navigationItem = new NavigationItem('sulu_test');

        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add($navigationItem);

        $this->assertEquals($navigationItem, $navigationItemCollection->get('sulu_test'));
    }

    public function testAll()
    {
        $navigationItem1 = new NavigationItem('sulu_test_1');
        $navigationItem2 = new NavigationItem('sulu_test_2');

        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->add($navigationItem1);
        $navigationItemCollection->add($navigationItem2);

        $routes = $navigationItemCollection->all();

        $this->assertContains($navigationItem1, $routes);
        $this->assertContains($navigationItem2, $routes);
    }

    public function testGetNotExistingRoute()
    {
        $this->expectException(NavigationItemNotFoundException::class);

        $navigationItemCollection = new NavigationItemCollection();
        $navigationItemCollection->get('not-existing');
    }
}
