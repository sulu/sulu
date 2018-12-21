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

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Exception\ParentRouteNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\RouteNotFoundException;

class RouteRegistryTest extends TestCase
{
    /**
     * @var RouteRegistry
     */
    protected $routeRegistry;

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

    public function setUp()
    {
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getAdmins()->willReturn([$this->admin1, $this->admin2]);

        $this->routeRegistry = new RouteRegistry($this->adminPool->reveal());
    }

    public function testFindRouteByName()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->setOption('value', 'test1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->setOption('value', 'test2');
        $route3 = new Route('test3', '/test3', 'test3');
        $route3->setOption('value', 'test3');
        $this->admin1->getRoutes()->willReturn([$route1]);
        $this->admin2->getRoutes()->willReturn([$route2, $route3]);

        $route = $this->routeRegistry->findRouteByName('test2');
        $this->assertEquals($route, $route2);
    }

    public function testFindRouteByNameException()
    {
        $this->expectException(RouteNotFoundException::class);

        $this->admin1->getRoutes()->willReturn([]);
        $this->admin2->getRoutes()->willReturn([]);

        $this->routeRegistry->findRouteByName('not_existing');
    }

    public function testGetRoutes()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->setOption('value', 'test1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->setOption('value', 'test2');
        $route3 = new Route('test3', '/test3', 'test3');
        $route3->setOption('value', 'test3');
        $this->admin1->getRoutes()->willReturn([$route1]);
        $this->admin2->getRoutes()->willReturn([$route2, $route3]);

        $routes = $this->routeRegistry->getRoutes();
        $this->assertCount(3, $routes);
        $this->assertEquals($route1, $routes[0]);
        $this->assertEquals($route2, $routes[1]);
        $this->assertEquals($route3, $routes[2]);
    }

    public function testGetRoutesMemoryCache()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->setOption('value', 'test1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->setOption('value', 'test2');
        $route3 = new Route('test3', '/test3', 'test3');
        $route3->setOption('value', 'test3');
        $this->admin1->getRoutes()->willReturn([$route1])->shouldBeCalledTimes(1);
        $this->admin2->getRoutes()->willReturn([$route2, $route3])->shouldBeCalledTimes(1);

        $routes1 = $this->routeRegistry->getRoutes();
        $routes2 = $this->routeRegistry->getRoutes();

        $this->assertSame($routes1, $routes2);
    }

    public function testRouteWithNonExistingParent()
    {
        $this->expectException(ParentRouteNotFoundException::class);

        $route = new Route('test1', '/test1', 'test1');
        $route->setParent('not-existing');
        $this->admin1->getRoutes()->willReturn([$route]);
        $this->admin2->getRoutes()->willReturn([]);

        $this->routeRegistry->getRoutes();
    }

    public function testRoutesMergeOptions()
    {
        $route1 = new Route('test1', '/test1', 'test1');
        $route1->setOption('route1', 'test1');
        $route1->setOption('override', 'override');
        $route1_1 = new Route('test1_1', '/test1_1', 'test1_1');
        $route1_1->setOption('route1_1', 'test1_1');
        $route1_1->setParent('test1');
        $route1_1_1 = new Route('test1_1_1', '/test1_1_1', 'test1_1_1');
        $route1_1_1->setOption('override', 'overriden-value');
        $route1_1_1->setOption('route1_1_1', 'test1_1_1');
        $route1_1_1->setParent('test1_1');
        $route2 = new Route('test2', '/test2', 'test2');
        $route2->setOption('value', 'test');

        $this->admin1->getRoutes()->willReturn([$route1, $route1_1, $route1_1_1, $route2]);
        $this->admin2->getRoutes()->willReturn([]);

        $routes = $this->routeRegistry->getRoutes();
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
}
