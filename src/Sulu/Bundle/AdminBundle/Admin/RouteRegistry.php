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
use Sulu\Bundle\AdminBundle\Exception\ParentRouteNotFoundException;
use Sulu\Bundle\AdminBundle\Exception\RouteNotFoundException;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;

class RouteRegistry
{
    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var AdminPool
     */
    private $adminPool;

    public function __construct(AdminPool $adminPool)
    {
        $this->adminPool = $adminPool;
    }

    /**
     * Returns all the routes for the frontend application from all Admin objects.
     *
     * @return Route[]
     */
    public function getRoutes(): array
    {
        if (!$this->routes) {
            $this->loadRoutes();
            $this->processRoutes();
        }

        return $this->routes;
    }

    public function findRouteByName(string $name): Route
    {
        foreach ($this->getRoutes() as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }

        throw new RouteNotFoundException($name);
    }

    private function loadRoutes(): void
    {
        $routes = [];
        foreach ($this->adminPool->getAdmins() as $admin) {
            if (!$admin instanceof RouteProviderInterface) {
                continue;
            }

            $routes = array_merge($routes, $admin->getRoutes());
        };

        $this->validateRoutes($routes);

        array_walk($routes, function(&$route, $index) {
            $route = clone $route;
        });

        $this->routes = $this->mergeRouteOptions($routes);
    }

    private function processRoutes(): void
    {
        foreach ($this->routes as $route) {
            if ($route->getParent()) {
                $route->updatePath($this->getRoutePath($route));
            }
        }
    }

    private function validateRoutes(array $routes): void
    {
        $routeNames = array_map(function(Route $route) {
            return $route->getName();
        }, $routes);

        foreach ($routes as $route) {
            $routeParent = $route->getParent();

            if (!$routeParent) {
                continue;
            }

            if (!in_array($routeParent, $routeNames)) {
                throw new ParentRouteNotFoundException($routeParent, $route->getName());
            }
        }
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

    private function getRoutePath(Route $route): string
    {
        if (!$route->getParent()) {
            return $route->getPath();
        }

        $parentRoute = $this->findRouteByName($route->getParent());

        return $parentRoute->getPath() . $route->getPath();
    }
}
