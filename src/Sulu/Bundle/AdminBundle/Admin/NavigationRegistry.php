<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class NavigationRegistry
{
    /**
     * @var Navigation
     */
    private $navigation;

    /**
     * @var AdminPool
     */
    private $adminPool;

    /**
     * @var RouteRegistry
     */
    private $routeRegistry;

    public function __construct(AdminPool $adminPool, RouteRegistry $routeRegistry)
    {
        $this->adminPool = $adminPool;
        $this->routeRegistry = $routeRegistry;
    }

    /**
     * Returns the navigation combined from all Admin objects.
     */
    public function getNavigation(): Navigation
    {
        if (!$this->navigation) {
            $this->loadNavigation();
        }

        return $this->navigation;
    }

    private function loadNavigation(): void
    {
        /** @var Navigation $navigation */
        $navigation = null;
        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof NavigationProviderInterface) {
                continue;
            }

            if (null === $navigation) {
                $navigation = $admin->getNavigationV2();

                continue;
            }

            $navigation = $navigation->merge($admin->getNavigationV2());
        };

        foreach ($navigation->getRoot()->getChildren() as $child) {
            $this->addChildRoutes($child);
        }

        $this->navigation = $navigation;
    }

    private function addChildRoutes(NavigationItem $navigationItem): void
    {
        if ($navigationItem->getMainRoute()) {
            $mainPath = $this->routeRegistry->findRouteByName($navigationItem->getMainRoute())->getPath();
            foreach ($this->routeRegistry->getRoutes() as $route) {
                if (strpos($route->getPath(), $mainPath) !== false) {
                    $navigationItem->addChildRoute($route->getName());
                }
            }
        }

        foreach ($navigationItem->getChildren() as $child) {
            $this->addChildRoutes($child);
        }
    }
}
