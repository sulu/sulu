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

trait TabViewBuilderTrait
{
    use TabViewChildBuilderTrait;

    public function addRouterAttributesToBlacklistToView(array $routerAttributesToBlacklist): void
    {
        $oldRouterAttributesToBlacklist = $this->view->getOption('routerAttributesToBlacklist');
        $newRouterAttributesToBlacklist = $oldRouterAttributesToBlacklist
            ? \array_merge($oldRouterAttributesToBlacklist, $routerAttributesToBlacklist)
            : $routerAttributesToBlacklist;

        $this->view->setOption('routerAttributesToBlacklist', $newRouterAttributesToBlacklist);
    }
}
