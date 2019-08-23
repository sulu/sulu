<?php

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

use Sulu\Bundle\AdminBundle\Admin\Routing\Route;

class RouteCollection
{
    /**
     * @var Route[]
     */
    private $routes = [];

    public function get(string $name): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->getName() === $name) {
                return $route;
            }
        }
    }

    /**
     * @param Route[] $routes
     */
    public function addRoutes(array $routes): void
    {
        $this->routes = array_merge($routes, $this->routes);
    }

    /**
     * @return Route[]
     */
    public function all(): array
    {
        return $this->routes;
    }
}
