<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Admin\View;

trait ToolbarActionsViewBuilderTrait
{
    /**
     * @param ToolbarAction[] $toolbarActions
     */
    private function addToolbarActionsToView(View $view, array $toolbarActions): void
    {
        $oldToolbarActions = $view->getOption('toolbarActions');
        $newToolbarActions = $oldToolbarActions
            ? array_merge($oldToolbarActions, $toolbarActions)
            : $toolbarActions;
        $view->setOption('toolbarActions', $newToolbarActions);
    }
}
