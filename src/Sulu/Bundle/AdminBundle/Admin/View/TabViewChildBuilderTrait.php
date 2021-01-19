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

trait TabViewChildBuilderTrait
{
    private function setTabTitleToView(View $view, string $tabTitle): void
    {
        $view->setOption('tabTitle', $tabTitle);
    }

    private function setTabConditionToView(View $view, string $tabCondition): void
    {
        $view->setOption('tabCondition', $tabCondition);
    }

    private function setTabOrderToView(View $view, int $tabOrder): void
    {
        $view->setOption('tabOrder', $tabOrder);
    }

    private function setTabPriorityToView(View $view, int $tabPriority): void
    {
        $view->setOption('tabPriority', $tabPriority);
    }

    private function addTabBadgeToView(View $view, Badge $badge, ?string $key = null): void
    {
        $tabBadges = $view->getOption('tabBadges') ?? [];

        if (null === $key) {
            $tabBadges[] = $badge->getConfiguration();
        } else {
            $tabBadges[$key] = $badge->getConfiguration();
        }

        $view->setOption('tabBadges', $tabBadges);
    }
}
