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

trait RouteBuilderTrait
{
    private function setResourceKeyToRoute(Route $route, string $resourceKey): void
    {
        $route->setOption('resourceKey', $resourceKey);
    }

    private function setTitleToRoute(Route $route, string $title): void
    {
        $route->setOption('title', $title);
    }

    private function setEditRouteToRoute(Route $route, string $editRoute): void
    {
        $route->setOption('editRoute', $editRoute);
    }

    private function addLocalesToRoute(Route $route, array $locales): void
    {
        $oldLocales = $route->getOption('locales');
        $newLocales = $oldLocales ? array_merge($oldLocales, $locales) : $locales;
        $route->setOption('locales', $newLocales);
    }

    private function setDefaultLocaleToRoute(Route $route, string $locale): void
    {
        $route->setAttributeDefault('locale', $locale);
    }

    private function addToolbarActionsToRoute(Route $route, array $toolbarActions): void
    {
        $oldToolbarActions = $route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $route->setOption('toolbarActions', $newToolbarActions);
    }

    private function setBackRouteToRoute(Route $route, string $backRoute): void
    {
        $route->setOption('backRoute', $backRoute);
    }

    private function setTabTitleToRoute(Route $route, string $tabTitle): void
    {
        $route->setOption('tabTitle', $tabTitle);
    }

    private function setTabOrderToRoute(Route $route, string $tabOrder): void
    {
        $route->setOption('tabOrder', $tabOrder);
    }

    private function setTabConditionToRoute(Route $route, string $tabCondition): void
    {
        $route->setOption('tabCondition', $tabCondition);
    }

    private function setTabPriorityToRoute(Route $route, string $tabPriority): void
    {
        $route->setOption('tabPriority', $tabPriority);
    }
}
