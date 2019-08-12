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
        $transformedToolbarActions = [];
        foreach ($toolbarActions as $toolbarActionKey => $toolbarActionOptions) {
            if (is_string($toolbarActionKey) && is_array($toolbarActionOptions)) {
                $transformedToolbarActions[$toolbarActionKey] = $toolbarActionOptions;
                continue;
            }

            if (is_numeric($toolbarActionKey) && is_string($toolbarActionOptions)) {
                $transformedToolbarActions[$toolbarActionOptions] = [];
                continue;
            }

            throw new \DomainException(
                'The definition of a toolbarAction is either a string key with an options array '
                . 'or a numeric key with a string value!'
            );
        }

        $oldToolbarActions = $route->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions
            ? array_merge($oldToolbarActions, $transformedToolbarActions)
            : $transformedToolbarActions;
        $route->setOption('toolbarActions', $newToolbarActions);
    }
}
