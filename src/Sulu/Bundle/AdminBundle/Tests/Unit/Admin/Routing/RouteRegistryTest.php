<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Admin\Routing;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\View\RouteBuilder;
use Sulu\Bundle\AdminBundle\Admin\View\RouteRegistry;
use Sulu\Bundle\AdminBundle\Exception\ParentRouteNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\RouteNotFoundException;
use Sulu\Bundle\TestBundle\Testing\ReadObjectAttributeTrait;

class RouteRegistryTest extends TestCase
{
    use ReadObjectAttributeTrait;

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

    public function setUp(): void
    {
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getAdmins()->willReturn([$this->admin1, $this->admin2]);

        $this->routeRegistry = new RouteRegistry($this->adminPool->reveal());
    }

    public function testFindRouteByName()
    {
        $routeBuilder1 = new RouteBuilder('test1', '/test1', 'test1');
        $routeBuilder1->setOption('value', 'test1');
        $routeBuilder2 = new RouteBuilder('test2', '/test2', 'test2');
        $routeBuilder2->setOption('value', 'test2');
        $routeBuilder3 = new RouteBuilder('test3', '/test3', 'test3');
        $routeBuilder3->setOption('value', 'test3');
        $this->admin1->configureViews(Argument::any())->will(function($arguments) use ($routeBuilder1) {
            $arguments[0]->add($routeBuilder1);
        });
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder2, $routeBuilder3) {
                $arguments[0]->add($routeBuilder2);
                $arguments[0]->add($routeBuilder3);
            }
        );

        $route = $this->routeRegistry->findRouteByName('test2');
        $this->assertEquals($route, $routeBuilder2->getRoute());
    }

    public function testFindRouteByNameException()
    {
        $this->expectException(RouteNotFoundException::class);

        $this->routeRegistry->findRouteByName('not_existing');
    }

    public function testGetRoutes()
    {
        $routeBuilder1 = new RouteBuilder('test1', '/test1', 'test1');
        $routeBuilder1->setOption('value', 'test1');
        $routeBuilder2 = new RouteBuilder('test2', '/test2', 'test2');
        $routeBuilder2->setOption('value', 'test2');
        $routeBuilder3 = new RouteBuilder('test3', '/test3', 'test3');
        $routeBuilder3->setOption('value', 'test3');

        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder1) {
                $arguments[0]->add($routeBuilder1);
            }
        )->shouldBeCalledTimes(1);
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder2, $routeBuilder3) {
                $arguments[0]->add($routeBuilder2);
                $arguments[0]->add($routeBuilder3);
            }
        )->shouldBeCalledTimes(1);

        $routes = $this->routeRegistry->getRoutes();
        $this->assertCount(3, $routes);
        $this->assertEquals($routeBuilder1->getRoute(), $routes[0]);
        $this->assertEquals($routeBuilder2->getRoute(), $routes[1]);
        $this->assertEquals($routeBuilder3->getRoute(), $routes[2]);
    }

    public function testGetRoutesMemoryCache()
    {
        $routeBuilder1 = new RouteBuilder('test1', '/test1', 'test1');
        $routeBuilder1->setOption('value', 'test1');
        $routeBuilder2 = new RouteBuilder('test2', '/test2', 'test2');
        $routeBuilder2->setOption('value', 'test2');
        $routeBuilder3 = new RouteBuilder('test3', '/test3', 'test3');
        $routeBuilder3->setOption('value', 'test3');
        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder1) {
                $arguments[0]->add($routeBuilder1);
            }
        )->shouldBeCalledTimes(1);
        $this->admin2->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder2, $routeBuilder3) {
                $arguments[0]->add($routeBuilder2);
                $arguments[0]->add($routeBuilder3);
            }
        )->shouldBeCalledTimes(1);

        $routes1 = $this->routeRegistry->getRoutes();
        $routes2 = $this->routeRegistry->getRoutes();

        $this->assertSame($routes1, $routes2);
    }

    public function testRouteWithNonExistingParent()
    {
        $this->expectException(ParentRouteNotFoundException::class);

        $routeBuilder = new RouteBuilder('test1', '/test1', 'test1');
        $routeBuilder->setParent('not-existing');
        $this->admin1->configureViews(Argument::any())->will(function($arguments) use ($routeBuilder) {
            $arguments[0]->add($routeBuilder);
        });

        $this->routeRegistry->getRoutes();
    }

    public function testRoutesMergeOptions()
    {
        $routeBuilder1 = new RouteBuilder('test1', '/test1', 'test1');
        $routeBuilder1->setOption('route1', 'test1');
        $routeBuilder1->setOption('override', 'override');
        $routeBuilder1_1 = new RouteBuilder('test1_1', '/test1_1', 'test1_1');
        $routeBuilder1_1->setOption('route1_1', 'test1_1');
        $routeBuilder1_1->setParent('test1');
        $routeBuilder1_1_1 = new RouteBuilder('test1_1_1', '/test1_1_1', 'test1_1_1');
        $routeBuilder1_1_1->setOption('override', 'overriden-value');
        $routeBuilder1_1_1->setOption('route1_1_1', 'test1_1_1');
        $routeBuilder1_1_1->setParent('test1_1');
        $routeBuilder2 = new RouteBuilder('test2', '/test2', 'test2');
        $routeBuilder2->setOption('value', 'test');

        $this->admin1->configureViews(Argument::any())->will(
            function($arguments) use ($routeBuilder1, $routeBuilder1_1, $routeBuilder1_1_1, $routeBuilder2) {
                $arguments[0]->add($routeBuilder1);
                $arguments[0]->add($routeBuilder1_1);
                $arguments[0]->add($routeBuilder1_1_1);
                $arguments[0]->add($routeBuilder2);
            }
        );

        $routes = $this->routeRegistry->getRoutes();
        $this->assertCount(4, $routes);
        $this->assertEquals(
            ['route1' => 'test1', 'override' => 'override'],
            $this->readObjectAttribute($routes[0], 'options')
        );
        $this->assertEquals(
            ['route1' => 'test1', 'route1_1' => 'test1_1', 'override' => 'override'],
            $this->readObjectAttribute($routes[1], 'options')
        );
        $this->assertEquals(
            [
                'route1' => 'test1',
                'route1_1' => 'test1_1',
                'route1_1_1' => 'test1_1_1',
                'override' => 'overriden-value',
            ],
            $this->readObjectAttribute($routes[2], 'options')
        );
        $this->assertEquals(['value' => 'test'], $this->readObjectAttribute($routes[3], 'options'));
    }
}
