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

use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;

/**
 * The AdminPool is a container for all the registered admin-objects.
 */
class AdminPool
{
    /**
     * The array for all the admin-objects.
     *
     * @var array
     */
    private $pool = [];

    /**
     * Returns all the registered admins.
     *
     * @return Admin[]
     */
    public function getAdmins()
    {
        return $this->pool;
    }

    /**
     * Adds a new admin.
     *
     * @param $admin
     */
    public function addAdmin($admin)
    {
        $this->pool[] = $admin;
    }

    /**
     * Returns all the routes for the frontend application from all Admin objects.
     */
    public function getRoutes(): array
    {
        $routes = [];
        $this->iterateAdmins(function(Admin $admin) use (&$routes) {
            $routes = array_merge($routes, $admin->getRoutes());
        });

        array_walk($routes, function(&$route, $index) {
            $route = clone $route;
        });

        return $this->mergeRouteOptions($routes);
    }

    private function mergeRouteOptions(array $routes, string $parent = null)
    {
        /** @var Route[] $childRoutes */
        $childRoutes = array_filter($routes, function(Route $route) use ($parent) {
            return $route->getParent() === $parent;
        });

        if (empty($childRoutes)) {
            return [];
        }

        /** @var Route $parentRoute */
        $parentRoutes = array_values(array_filter($routes, function(Route $route) use ($parent) {
            return $route->getName() === $parent;
        }));

        $parentRoute = null;
        if (!empty($parentRoutes)) {
            $parentRoute = $parentRoutes[0];
        }

        $mergedRoutes = [];
        foreach ($childRoutes as $childRoute) {
            $mergedRoutes[] = $parentRoute ? $childRoute->mergeRoute($parentRoute) : $childRoute;
            $mergedRoutes = array_merge($mergedRoutes, $this->mergeRouteOptions($routes, $childRoute->getName()));
        }

        return $mergedRoutes;
    }

    /**
     * Returns the navigation combined from all Admin objects.
     *
     * @return Navigation
     */
    public function getNavigation()
    {
        /** @var Navigation $navigation */
        $navigation = null;
        $this->iterateAdmins(function(Admin $admin) use (&$navigation) {
            if (null === $navigation) {
                $navigation = $admin->getNavigation();

                return;
            }
            $navigation = $navigation->merge($admin->getNavigation());
        });

        return $navigation;
    }

    /**
     * Returns the combined security contexts from all Admin objects.
     *
     * @return array
     */
    public function getSecurityContexts()
    {
        $contexts = [];
        $this->iterateAdmins(function(Admin $admin) use (&$contexts) {
            $contexts = array_merge_recursive($contexts, $admin->getSecurityContexts());
        });

        return $contexts;
    }

    /**
     * Helper function to iterate over all available Admin objects.
     */
    private function iterateAdmins(callable $callback): void
    {
        foreach ($this->pool as $admin) {
            $callback($admin);
        }
    }
}
