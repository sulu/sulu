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

trait TabRouteBuilderTrait
{
    private function setTabTitleToRoute(Route $route, string $tabTitle): void
    {
        $route->setOption('tabTitle', $tabTitle);
    }

    private function setTabConditionToRoute(Route $route, string $tabCondition): void
    {
        $route->setOption('tabCondition', $tabCondition);
    }

    private function setTabOrderToRoute(Route $route, int $tabOrder): void
    {
        $route->setOption('tabOrder', $tabOrder);
    }

    private function setTabPriorityToRoute(Route $route, int $tabPriority): void
    {
        $route->setOption('tabPriority', $tabPriority);
    }
}
