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
use Prophecy\Argument;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\AdminPool;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Admin\Routing\RouteRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationRegistryTest extends TestCase
{
    /**
     * @var NavigationRegistry
     */
    protected $navigationRegistry;

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

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function setUp(): void
    {
        $this->admin1 = $this->prophesize(Admin::class);
        $this->admin2 = $this->prophesize(Admin::class);

        $this->adminPool = $this->prophesize(AdminPool::class);
        $this->adminPool->getAdmins()->willReturn([$this->admin1, $this->admin2]);

        $this->translator = $this->prophesize(TranslatorInterface::class);

        $this->routeRegistry = $this->prophesize(RouteRegistry::class);

        $this->navigationRegistry = new NavigationRegistry(
            $this->translator->reveal(),
            $this->adminPool->reveal(),
            $this->routeRegistry->reveal()
        );
    }

    public function testGetNavigation()
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setMainRoute('route1');

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1) {
            $arguments[0]->add($navigationItem1);
        });

        $navigationItem2 = new NavigationItem('navigation_2');
        $navigationChildItem1 = new NavigationItem('navigation_2_child_1');
        $navigationChildItem1->setMainRoute('route2_child1');
        $navigationChildItem2 = new NavigationItem('navigation_2_child_2');
        $navigationChildItem2->setMainRoute('route2_child2');
        $navigationItem2->addChild($navigationChildItem1);
        $navigationItem2->addChild($navigationChildItem2);

        $this->admin2->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem2) {
            $arguments[0]->add($navigationItem2);
        });

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/route1');
        $route1->getName()->willReturn('route1');

        $route2Child1 = $this->prophesize(Route::class);
        $route2Child1->getPath()->willReturn('/route2-child1');
        $route2Child1->getName()->willReturn('route2_child1');

        $route2Child2 = $this->prophesize(Route::class);
        $route2Child2->getPath()->willReturn('/route2-child2');
        $route2Child2->getName()->willReturn('route2_child2');

        $route2Child2Details = $this->prophesize(Route::class);
        $route2Child2Details->getPath()->willReturn('/route2-child2/details');
        $route2Child2Details->getName()->willReturn('route2_child2_details');

        $this->routeRegistry->getRoutes()->willReturn(
            [
                $route1->reveal(),
                $route2Child1->reveal(),
                $route2Child2->reveal(),
                $route2Child2->reveal(),
            ]
        );

        $this->routeRegistry->findRouteByName('route1')->shouldBeCalled()->willReturn($route1->reveal());
        $this->routeRegistry->findRouteByName('route2_child1')->shouldBeCalled()
            ->willReturn($route2Child1->reveal());
        $this->routeRegistry->findRouteByName('route2_child2')->shouldBeCalled()
            ->willReturn($route2Child2->reveal());

        $this->translator->trans('navigation_1', [], 'admin')->willReturn('Navigation 1');
        $this->translator->trans('navigation_2', [], 'admin')->willReturn('Navigation 2');
        $this->translator->trans('navigation_2_child_1', [], 'admin')->willReturn('Navigation 2 - Child 1');
        $this->translator->trans('navigation_2_child_2', [], 'admin')->willReturn('Navigation 2 - Child 2');

        $navigationItems = $this->navigationRegistry->getNavigationItems();
        $this->assertCount(2, $navigationItems);
        $this->assertEquals('Navigation 1', $navigationItems[0]->getLabel());
        $this->assertEquals('Navigation 2', $navigationItems[1]->getLabel());

        // check for children of first navigation
        $this->assertCount(2, $navigationItems[1]->getChildren());
        $this->assertEquals(
            'Navigation 2 - Child 1',
            $navigationItems[1]->getChildren()[0]->getLabel()
        );
        // check for created child routes
        $this->assertCount(
            1,
            $navigationItems[1]->getChildren()[0]->getChildRoutes()
        );
        $this->assertEquals(
            'route2_child1',
            $navigationItems[1]->getChildren()[0]->getChildRoutes()[0]
        );
        // check for "Navigation 2 - Child 2"
        $this->assertEquals(
            'Navigation 2 - Child 2',
            $navigationItems[1]->getChildren()[1]->getLabel()
        );
    }

    public function testGetNavigationMemoryCache()
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setMainRoute('route1');

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1) {
            $arguments[0]->add($navigationItem1);
        })->shouldBeCalledTimes(1);

        $this->admin2->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1) {
            $arguments[0]->add($navigationItem1);
        })->shouldBeCalledTimes(1);

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/route1');
        $route1->getName()->willReturn('route1');

        $this->routeRegistry->getRoutes()->willReturn([$route1->reveal()])
            ->shouldBeCalledTimes(1);

        $this->routeRegistry->findRouteByName('route1')->shouldBeCalled()
            ->willReturn($route1->reveal())->shouldBeCalledTimes(1);

        $this->navigationRegistry->getNavigationItems();
    }

    public function testGetNavigationWithChildren()
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setMainRoute('route1');

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/route1');
        $route1->getName()->willReturn('route1');

        $route11 = $this->prophesize(Route::class);
        $route11->getPath()->willReturn('/route1/child1');
        $route11->getName()->willReturn('route11');

        $route21 = $this->prophesize(Route::class);
        $route21->getPath()->willReturn('/route2/route1');
        $route21->getName()->willReturn('route2_1');

        $this->admin1->configureNavigationItems(Argument::any())->will(function($arguments) use ($navigationItem1) {
            $arguments[0]->add($navigationItem1);
        });

        $this->routeRegistry->getRoutes()->willReturn([$route1, $route11, $route21]);
        $this->routeRegistry->findRouteByName('route1')->willReturn($route1);

        $navigation = $this->navigationRegistry->getNavigationItems();

        $this->assertEquals(['route1', 'route11'], $navigation[0]->getChildRoutes());
    }

    public function testGetNavigationWithChildrenSlashOnly()
    {
        $navigationItem1 = new NavigationItem('navigation_1');
        $navigationItem1->setMainRoute('route1');

        $navigationItem2 = new NavigationItem('navigation_2');
        $navigationItem2->setMainRoute('route2');

        $this->admin1->configureNavigationItems(Argument::any())
             ->will(function($arguments) use ($navigationItem1, $navigationItem2) {
                 $arguments[0]->add($navigationItem1);
                 $arguments[0]->add($navigationItem2);
             });

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/');
        $route1->getName()->willReturn('route1');

        $route2 = $this->prophesize(Route::class);
        $route2->getPath()->willReturn('/route2');
        $route2->getName()->willReturn('route2');

        $this->routeRegistry->getRoutes()->willReturn([$route1, $route2]);
        $this->routeRegistry->findRouteByName('route1')->willReturn($route1);
        $this->routeRegistry->findRouteByName('route2')->willReturn($route2);

        $navigationItems = $this->navigationRegistry->getNavigationItems();

        $this->assertEquals([], $navigationItems[0]->getChildRoutes());
        $this->assertEquals(['route2'], $navigationItems[1]->getChildRoutes());
    }
}
