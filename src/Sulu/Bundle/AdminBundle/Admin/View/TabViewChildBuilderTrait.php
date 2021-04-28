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

    private function setTabGapToView(View $view, bool $enableTabGap): void
    {
        $view->setOption('tabGap', $enableTabGap);
    }

    /**
     * @param Badge[] $badges
     */
    private function addTabBadgesToView(View $view, array $badges): void
    {
        $oldBadges = $view->getOption('tabBadges');
        $newBadges = $oldBadges
            ? \array_merge($oldBadges, $badges)
            : $badges;
        $view->setOption('tabBadges', $newBadges);
    }
}
