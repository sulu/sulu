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
    use ToolbarActionsRouteBuilderTrait;

    private function setResourceKeyToRoute(Route $route, string $resourceKey): void
    {
        $route->setOption('resourceKey', $resourceKey);
    }

    private function setFormKeyToRoute(Route $route, string $formKey): void
    {
        $route->setOption('formKey', $formKey);
    }

    private function setApiOptionsToRoute(Route $route, array $apiOptions): void
    {
        $route->setOption('apiOptions', $apiOptions);
    }

    private function setBackRouteToRoute(Route $route, string $backRoute): void
    {
        $route->setOption('backRoute', $backRoute);
    }

    private function setEditRouteToRoute(Route $route, string $editRoute): void
    {
        $route->setOption('editRoute', $editRoute);
    }

    private function setIdQueryParameterToRoute(Route $route, string $idQueryParameter): void
    {
        $route->setOption('idQueryParameter', $idQueryParameter);
    }

    private function setTitleVisibleToRoute(Route $route, bool $titleVisible): void
    {
        $route->setOption('titleVisible', $titleVisible);
    }

    private function addLocalesToRoute(Route $route, array $locales): void
    {
        $oldLocales = $route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $route->setOption('locales', $newLocales);
    }

    private function addRouterAttributesToFormRequestToRoute(Route $route, array $routerAttributesToFormRequest): void
    {
        $oldRouterAttributesToFormRequest = $route->getOption('routerAttributesToFormRequest');
        $newRouterAttributesToFormRequest = $oldRouterAttributesToFormRequest
            ? array_merge($oldRouterAttributesToFormRequest, $routerAttributesToFormRequest)
            : $routerAttributesToFormRequest;

        $route->setOption('routerAttributesToFormRequest', $newRouterAttributesToFormRequest);
    }

    private function addRouterAttributesToEditRouteToRoute(Route $route, array $routerAttributesToEditRoute): void
    {
        $oldRouterAttributesToEditRoute = $route->getOption('routerAttributesToEditRoute');
        $newRouterAttributesToEditRoute = $oldRouterAttributesToEditRoute
            ? array_merge($oldRouterAttributesToEditRoute, $routerAttributesToEditRoute)
            : $routerAttributesToEditRoute;

        $route->setOption('routerAttributesToEditRoute', $newRouterAttributesToEditRoute);
    }

    private function addRouterAttributesToBackRouteToRoute(Route $route, array $routerAttributesToBackRoute): void
    {
        $oldRouterAttributesToBackRoute = $route->getOption('routerAttributesToBackRoute');
        $newRouterAttributesToBackRoute = $oldRouterAttributesToBackRoute
            ? array_merge($oldRouterAttributesToBackRoute, $routerAttributesToBackRoute)
            : $routerAttributesToBackRoute;

        $route->setOption('routerAttributesToBackRoute', $newRouterAttributesToBackRoute);
    }
}
