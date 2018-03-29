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
use Sulu\Bundle\AdminBundle\Admin\NavigationRegistry;
use Sulu\Bundle\AdminBundle\Admin\RouteRegistry;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Symfony\Component\Translation\TranslatorInterface;

class NavigationRegistryTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
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
        $rootItem1 = new NavigationItem('Root');

        $navigation1 = new NavigationItem('navigation_1');
        $navigation1->setMainRoute('route1');

        $rootItem1->addChild($navigation1);
        $this->admin1->getNavigationV2()->willReturn(new Navigation($rootItem1));

        $rootItem2 = new NavigationItem('Root');

        $navigation2 = new NavigationItem('navigation_2');
        $navigationChild1 = new NavigationItem('navigation_2_child_1');
        $navigationChild1->setMainRoute('route2_child1');
        $navigationChild2 = new NavigationItem('navigation_2_child_2');
        $navigationChild2->setMainRoute('route2_child2');
        $navigation2->addChild($navigationChild1);
        $navigation2->addChild($navigationChild2);

        $rootItem2->addChild($navigation2);
        $this->admin2->getNavigationV2()->willReturn(new Navigation($rootItem2));

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/route1');
        $route1->getName()->willReturn('route1');

        $route2Child1 = $this->prophesize(Route::class);
        $route2Child1->getPath()->willReturn('/route2-child1');
        $route2Child1->getName()->willReturn('route2_child1');

        $route2Child2 = $this->prophesize(Route::class);
        $route2Child2->getPath()->willReturn('/route2-child2');
        $route2Child2->getName()->willReturn('route2_child2');

        $route2Child2Detail = $this->prophesize(Route::class);
        $route2Child2Detail->getPath()->willReturn('/route2-child2/detail');
        $route2Child2Detail->getName()->willReturn('route2_child2_detail');

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

        $this->translator->trans('navigation_1', [], 'admin_backend')->willReturn('Navigation 1');
        $this->translator->trans('navigation_2', [], 'admin_backend')->willReturn('Navigation 2');
        $this->translator->trans('navigation_2_child_1', [], 'admin_backend')->willReturn('Navigation 2 - Child 1');
        $this->translator->trans('navigation_2_child_2', [], 'admin_backend')->willReturn('Navigation 2 - Child 2');

        $navigation = $this->navigationRegistry->getNavigation();
        $this->assertCount(2, $navigation->getRoot()->getChildren());
        $this->assertEquals('Navigation 1', $navigation->getRoot()->getChildren()[0]->getLabel());
        $this->assertEquals('Navigation 2', $navigation->getRoot()->getChildren()[1]->getLabel());

        // check for children of first navigation
        $this->assertCount(2, $navigation->getRoot()->getChildren()[1]->getChildren());
        $this->assertEquals(
            'Navigation 2 - Child 1',
            $navigation->getRoot()->getChildren()[1]->getChildren()[0]->getLabel()
        );
        // check for created child routes
        $this->assertCount(
            1,
            $navigation->getRoot()->getChildren()[1]->getChildren()[0]->getChildRoutes()
        );
        $this->assertEquals(
            'route2_child1',
            $navigation->getRoot()->getChildren()[1]->getChildren()[0]->getChildRoutes()[0]
        );
        // check for "Navigation 2 - Child 2"
        $this->assertEquals(
            'Navigation 2 - Child 2',
            $navigation->getRoot()->getChildren()[1]->getChildren()[1]->getLabel()
        );
    }

    public function testGetNavigationMemoryCache()
    {
        $rootItem1 = new NavigationItem('Root');

        $navigation1 = new NavigationItem('navigation_1');
        $navigation1->setMainRoute('route1');

        $rootItem1->addChild($navigation1);
        $this->admin1->getNavigationV2()->willReturn(new Navigation($rootItem1))->shouldBeCalledTimes(1);
        $this->admin2->getNavigationV2()->willReturn(new Navigation($rootItem1))->shouldBeCalledTimes(1);

        $route1 = $this->prophesize(Route::class);
        $route1->getPath()->willReturn('/route1');
        $route1->getName()->willReturn('route1');

        $this->routeRegistry->getRoutes()->willReturn([$route1->reveal()])
            ->shouldBeCalledTimes(1);

        $this->routeRegistry->findRouteByName('route1')->shouldBeCalled()
            ->willReturn($route1->reveal())->shouldBeCalledTimes(1);

        $this->navigationRegistry->getNavigation();
    }
}
