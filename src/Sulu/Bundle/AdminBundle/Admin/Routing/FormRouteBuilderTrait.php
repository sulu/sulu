<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\Routing;

trait FormRouteBuilderTrait
{
    private function setFormKeyToRoute(Route $route, string $formKey): void
    {
        $route->setOption('formKey', $formKey);
    }

    private function setIdQueryParameterToRoute(Route $route, string $idQueryParameter): void
    {
        $route->setOption('idQueryParameter', $idQueryParameter);
    }

    private function addRouterAttributesToFormStoreToRoute(Route $route, array $routerAttributesToFormStore): void
    {
        $oldRouterAttributesToFormStore = $route->getOption('routerAttributesToFormStore');
        $newRouterAttributesToFormStore = $oldRouterAttributesToFormStore
            ? array_merge($oldRouterAttributesToFormStore, $routerAttributesToFormStore)
            : $routerAttributesToFormStore;

        $route->setOption('routerAttributesToFormStore', $newRouterAttributesToFormStore);
    }

    private function addRouterAttributesToEditRouteToRoute(Route $route, array $routerAttributesToEditRoute): void
    {
        $oldRouterAttributesToEditRoute = $route->getOption('routerAttributesToEditRoute');
        $newRouterAttributesToEditRoute = $oldRouterAttributesToEditRoute
            ? array_merge($oldRouterAttributesToEditRoute, $routerAttributesToEditRoute)
            : $routerAttributesToEditRoute;

        $route->setOption('routerAttributesToEditRoute', $newRouterAttributesToEditRoute);
    }
}
