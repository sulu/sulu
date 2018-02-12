<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Symfony\Component\Console\Command\Command;

class AdminPoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AdminPool
     */
    protected $adminPool;

    /**
     * @var Admin
     */
    protected $admin1;

    /**
     * @var Admin
     */
    protected $admin2;

    /**
     * @var Command
     */
    protected $command;

    public function setUp()
    {
        $this->adminPool = new AdminPool();
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool->addAdmin($this->admin1->reveal());
        $this->adminPool->addAdmin($this->admin2->reveal());
    }

    public function testAdmins()
    {
        $this->assertEquals(2, count($this->adminPool->getAdmins()));
        $this->assertSame($this->admin1->reveal(), $this->adminPool->getAdmins()[0]);
        $this->assertSame($this->admin2->reveal(), $this->adminPool->getAdmins()[1]);
    }

    public function testRoutes()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->addOption('value', 'test1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->addOption('value', 'test2');
        $route3 = new Route('test3', '/test3', 'test3');
        $route3->addOption('value', 'test3');
        $this->admin1->getRoutes()->willReturn([$route1]);
        $this->admin2->getRoutes()->willReturn([$route2, $route3]);

        $routes = $this->adminPool->getRoutes();
        $this->assertCount(3, $routes);
        $this->assertEquals($route1, $routes[0]);
        $this->assertEquals($route2, $routes[1]);
        $this->assertEquals($route3, $routes[2]);
    }

    public function testRouteWithNonExistingParent()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        $route = new Route('test1', '/test1', 'test1');
        $route->setParent('not-existing');
        $this->admin1->getRoutes()->willReturn([$route]);
        $this->admin2->getRoutes()->willReturn([]);

        $routes = $this->adminPool->getRoutes();
    }

    public function testRoutesMergeOptions()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->addOption('route1', 'test1');
        $route1->addOption('override', 'override');
        $route1_1 = new Route('test1_1', '/test1_1', 'test1_1');
        $route1_1->addOption('route1_1', 'test1_1');
        $route1_1->setParent('test1');
        $route1_1_1 = new Route('test1_1_1', '/test1_1_1', 'test1_1_1');
        $route1_1_1->addOption('override', 'overriden-value');
        $route1_1_1->addOption('route1_1_1', 'test1_1_1');
        $route1_1_1->setParent('test1_1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->addOption('value', 'test');

        $this->admin1->getRoutes()->willReturn([$route1, $route1_1, $route1_1_1, $route2]);
        $this->admin2->getRoutes()->willReturn([]);

        $routes = $this->adminPool->getRoutes();
        $this->assertCount(4, $routes);
        $this->assertAttributeEquals(
            ['route1' => 'test1', 'override' => 'override'],
            'options',
            $routes[0]
        );
        $this->assertAttributeEquals(
            ['route1' => 'test1', 'route1_1' => 'test1_1', 'override' => 'override'],
            'options',
            $routes[1]
        );
        $this->assertAttributeEquals(
            [
                'route1' => 'test1',
                'route1_1' => 'test1_1',
                'route1_1_1' => 'test1_1_1',
                'override' => 'overriden-value',
            ],
            'options',
            $routes[2]
        );
        $this->assertAttributeEquals(['value' => 'test'], 'options', $routes[3]);
    }

    public function testNavigation()
    {
        $rootItem1 = new NavigationItem('Root');
        $rootItem1->addChild(new NavigationItem('Child1'));
        $this->admin1->getNavigation()->willReturn(new Navigation($rootItem1));

        $rootItem2 = new NavigationItem('Root');
        $rootItem2->addChild(new NavigationItem('Child2'));
        $this->admin2->getNavigation()->willReturn(new Navigation($rootItem2));

        $navigation = $this->adminPool->getNavigation();
        $this->assertEquals('Child1', $navigation->getRoot()->getChildren()[0]->getName());
        $this->assertEquals('Child2', $navigation->getRoot()->getChildren()[1]->getName());
    }

    public function testSecurityContexts()
    {
        $this->admin1->getSecurityContexts()->willReturn([
            'Sulu' => [
                'Assets' => [
                    'assets.videos',
                    'assets.pictures',
                    'assets.documents',
                ],
            ],
        ]);

        $this->admin2->getSecurityContexts()->willReturn([
            'Sulu' => [
                'Portal' => [
                    'portals.com',
                    'portals.de',
                ],
            ],
        ]);

        $contexts = $this->adminPool->getSecurityContexts();

        $this->assertEquals(
            [
                'assets.videos',
                'assets.pictures',
                'assets.documents',
            ],
            $contexts['Sulu']['Assets']
        );
        $this->assertEquals(
            [
                'portals.com',
                'portals.de',
            ],
            $contexts['Sulu']['Portal']
        );
    }
}
