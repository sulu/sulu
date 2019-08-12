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

trait ToolbarActionsRouteBuilderTrait
{
    private function addToolbarActionsToRoute(Route $route, array $toolbarActions): void
    {
        $oldToolbarActions = $route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions ? array_merge($oldToolbarActions, $toolbarActions) : $toolbarActions;
        $route->setOption('toolbarActions', $newToolbarActions);
    }
}
