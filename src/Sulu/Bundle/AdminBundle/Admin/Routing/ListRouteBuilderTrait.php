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

trait ListRouteBuilderTrait
{
    private function setListKeyToRoute(Route $route, string $listKey): void
    {
        $route->setOption('listKey', $listKey);
    }

    private function addListAdaptersToRoute(Route $route, array $listAdapters): void
    {
        $oldListAdapters = $route->getOption('adapters');
        $newListAdapters = $oldListAdapters ? array_merge($oldListAdapters, $listAdapters) : $listAdapters;
        $route->setOption('adapters', $newListAdapters);
    }

    private function setAddRouteToRoute(Route $route, string $addRoute): void
    {
        $route->setOption('addRoute', $addRoute);
    }

    private function setSearchableToRoute(Route $route, bool $searchable): void
    {
        $route->setOption('searchable', $searchable);
    }

    private function addRouterAttributesToListStoreToRoute(Route $route, array $routerAttributesToListStore): void
    {
        $oldRouterAttributesToListStore = $route->getOption('routerAttributesToListStore');
        $newRouterAttributesToListStore = $oldRouterAttributesToListStore
            ? array_merge($oldRouterAttributesToListStore, $routerAttributesToListStore)
            : $routerAttributesToListStore;

        $route->setOption('routerAttributesToListStore', $newRouterAttributesToListStore);
    }
}
